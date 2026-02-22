<?php

namespace App\Http\Controllers;

use App\Models\AttendancePolicy;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class BiometricAttendanceController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-biometric-attendance')) {
            $company_setting = settings();
            $api_urls = !empty($company_setting['zkteco_api_url']) ? $company_setting['zkteco_api_url'] : '';
            $username = !empty($company_setting['zkteco_username']) ? $company_setting['zkteco_username'] : '';
            $password = !empty($company_setting['zkteco_password']) ? $company_setting['zkteco_password'] : '';
            $token = !empty($company_setting['zkteco_auth_token']) ? $company_setting['zkteco_auth_token'] : '';
            $isZktecoSync = !empty($company_setting['isZktecoSync']) && $company_setting['isZktecoSync'] == '1';

            $configurationMissing = empty($api_urls) || empty($username) || empty($password) || empty($token) || !$isZktecoSync;

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start_date = date('Y-m-d H:i:s', strtotime($request->start_date));
                $end_date = date('Y-m-d H:i:s', strtotime($request->end_date) + 86400 - 1);
            } else {
                $start_date = date('Y-m-d', strtotime('-7 days'));
                $end_date = date('Y-m-d');
            }

            $attendances = [];

            if (!empty($token) && !empty($api_urls)) {
                $api_url = rtrim($api_urls, '/');
                $url = $api_url . '/iclock/api/transactions/?' . http_build_query([
                    'start_time' => $start_date,
                    'end_time' => $end_date,
                    'page_size' => 10000,
                ]);

                $curl = curl_init();
                try {
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'Authorization: Token ' . $token
                        ),
                    ));

                    $response = curl_exec($curl);
                    curl_close($curl);

                    $json_attendance = json_decode($response, true);
                    $attendances = $json_attendance['data'] ?? [];
                } catch (\Throwable $th) {
                    $attendances = [];
                }
            }


            if (isDemo()) {
                $attendances = isSaas() ? $this->getSaasDemoData() : $this->getNonSaasDemoData();
            }


            // Group by employee code and date
            $groupedAttendances = collect($attendances)
                ->groupBy(function ($item) {
                    return $item['emp_code'] . '_' . date('Y-m-d', strtotime($item['punch_time']));
                })
                ->map(function ($dayEntries) {
                    $sorted = $dayEntries->sortBy('punch_time');
                    $firstEntry = $sorted->first();
                    $lastEntry = $sorted->last();
                    $employee = Employee::with('user')->where('biometric_emp_id', $firstEntry['emp_code'])->first();

                    return [
                        'id' => $firstEntry['id'],
                        'employee_code' => $firstEntry['emp_code'],
                        'name' => $employee && $employee->user ? $employee->user->name : 'Unknown',
                        'date' => date('Y-m-d', strtotime($firstEntry['punch_time'])),
                        'clock_in' => date('H:i:s', strtotime($firstEntry['punch_time'])),
                        'clock_out' => $sorted->count() > 1 ? date('H:i:s', strtotime($lastEntry['punch_time'])) : null,
                        'total_entries' => $sorted->count(),
                    ];
                });
            $query = $groupedAttendances->values();

            // Handle search
            if ($request->has('search') && !empty($request->search)) {
                $query = $query->filter(function ($item) use ($request) {
                    return stripos($item['name'], $request->search) !== false ||
                        stripos($item['employee_code'], $request->search) !== false;
                });
            }

            // Handle date range filter
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query = $query->filter(function ($item) use ($request) {
                    return $item['date'] = $request->start_date;
                });
            }

            if ($request->has('end_date') && !empty($request->end_date)) {
                $query = $query->filter(function ($item) use ($request) {
                    return $item['date'] <= $request->end_date;
                });
            }

            // Handle sorting
            if ($request->has('sort_field') && !empty($request->sort_field)) {
                $direction = $request->sort_direction ?? 'asc';
                $query = $query->sortBy($request->sort_field, SORT_REGULAR, $direction === 'desc');
            } else {
                $query = $query->sortByDesc('id');
            }

            // Manual pagination for collection
            $perPage = $request->per_page ?? 10;
            $currentPage = $request->page ?? 1;
            $total = $query->count();
            $items = $query->forPage($currentPage, $perPage)->values();

            $biometricData = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );
            return Inertia::render('hr/biometric-attendance/index', [
                'biometricData' => $biometricData,
                'filters' => $request->all(['search', 'start_date', 'end_date', 'sort_field', 'sort_direction', 'per_page']),
                'configurationMissing' => $configurationMissing,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function show(Request $request, $employeeCode, $date)
    {
        try {
            if (!Auth::user()->can('view-biometric-attendance')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission denied'
                ], 403);
            }

            if (isDemo()) {
                $allDemoData = isSaas() ? $this->getSaasDemoData() : $this->getNonSaasDemoData();
                $attendances = collect($allDemoData)
                    ->filter(function ($item) use ($employeeCode, $date) {
                        return $item['emp_code'] === $employeeCode &&
                            date('Y-m-d', strtotime($item['punch_time'])) === $date;
                    })
                    ->values()
                    ->toArray();
            } else {
                $company_setting = settings();
                $token = $company_setting['zkteco_auth_token'] ?? '';
                $api_urls = $company_setting['zkteco_api_url'] ?? '';

                if (empty($token) || empty($api_urls)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ZKTeco API configuration missing'
                    ], 400);
                }

                $start_date = $date . ' 00:00:00';
                $end_date = $date . ' 23:59:59';

                $api_url = rtrim($api_urls, '/');
                $url = $api_url . '/iclock/api/transactions/?' . http_build_query([
                    'emp_code' => $employeeCode,
                    'start_time' => $start_date,
                    'end_time' => $end_date,
                    'page_size' => 1000,
                ]);

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Token ' . $token
                    ],
                ]);

                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($httpCode !== 200 || !$response) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to fetch data from ZKTeco API'
                    ], 500);
                }

                $json_attendance = json_decode($response, true);
                $attendances = $json_attendance['data'] ?? [];
            }


            // Process entries (already filtered by API)
            $dayEntries = collect($attendances)
                ->sortBy('punch_time')
                ->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'punch_time' => $item['punch_time'],
                        'time' => date('H:i:s', strtotime($item['punch_time'])),
                        'punch_state_display' => $item['punch_state_display'] ?? 'Unknown',
                        'verify_type_display' => $item['verify_type_display'] ?? 'Unknown',
                        'terminal_alias' => $item['terminal_alias'] ?? 'Unknown'
                    ];
                })
                ->values();

            if ($dayEntries->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No entries found for this employee and date'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $dayEntries,
                    'employee_code' => $employeeCode,
                    'date' => $date
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance details'
            ], 500);
        }
    }

    public function sync(Request $request, $id)
    {
        if (Auth::user()->can('sync-biometric-attendance')) {
            $biometricEmpId = $request->biometric_emp_id;
            $biometricId = $request->biometric_id;
            // $punchTime = $request->punch_time;
            $attedanceDate = $request->date;
            $clockInTime = $request->clock_in;
            $clockOutTime = $request->clock_out;

            $company_setting = settings();

            if (empty($company_setting['zkteco_auth_token'])) {
                return redirect()->back()->with('error', __('Create the Auth Token From the Setting page.'));
            }

            $employee = Employee::with('user')->whereIn('created_by', getCompanyAndUsersId())->where('biometric_emp_id', $biometricEmpId)->first();

            if ($employee) {

                if (is_null($clockOutTime)) {
                    return redirect()->back()->with('error', __("Still Employee is not Clock Out. So You Can't Sync That Attedance."));
                }

                // Check Attendance Already Sync or not 
                $attendance = AttendanceRecord::where('biometric_id', $biometricId)
                    ->where('date', $attedanceDate)
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->first();

                if ($attendance) {
                    return redirect()->back()->with('error', __('Attendance Is Alredady Sync.'));
                }

                // Check if record already exists
                $exists = AttendanceRecord::where('employee_id', $employee->user_id)
                    ->where('date', $attedanceDate)
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->exists();

                if ($exists) {
                    return redirect()->back()->with('error', __('Attendance record already exists for this employee and date.'));
                } else {
                    $shift = Shift::where('id', $employee->shift_id)
                        ->where('status', 'active')
                        ->first();

                    if (!$shift) {
                        $shift = Shift::whereIn('created_by', getCompanyAndUsersId())
                            ->where('status', 'active')
                            ->first();
                    }

                    $policy = AttendancePolicy::where('id', $employee->attendance_policy_id)
                        ->where('status', 'active')
                        ->first();

                    if (!$policy) {
                        $policy = AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())
                            ->where('status', 'active')
                            ->first();
                    }


                    $attendance = new AttendanceRecord();
                    $attendance->employee_id = $employee->user_id;
                    $attendance->biometric_id = $biometricId;
                    $attendance->shift_id = $shift?->id;
                    $attendance->attendance_policy_id = $policy?->id;
                    $attendance->date = $attedanceDate;
                    $attendance->clock_in = $clockInTime;
                    $attendance->clock_out = $clockOutTime;
                    $attendance->created_by = creatorId();
                    $attendance->save();

                    $attendance->fresh(); // Reload to get relationships
                    $attendance->processAttendance();

                    return redirect()->back()->with('success', __('Biometric data synced successfully.'));
                }
            } else {
                return redirect()->back()->with('error', __('Employee not found'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function getSaasDemoData()
    {
        $data = [

            // 10/01/2025
            [
                "id" => 2285,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 12:54:23"
            ],
            [
                "id" => 2286,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 13:05:23"
            ],
            [
                "id" => 2287,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 14:05:23"
            ],
            [
                "id" => 2288,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 18:35:23"
            ],


            [
                "id" => 2289,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 09:05:23"
            ],
            [
                "id" => 2290,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 18:35:23"
            ],


            [
                "id" => 2291,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 09:05:00"
            ],
            [
                "id" => 2292,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 18:35:00"
            ],

            // 10/02/2025
            [
                "id" => 2293,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 09:10:23"
            ],
            [
                "id" => 2294,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 13:05:23"
            ],
            [
                "id" => 2295,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 14:05:23"
            ],
            [
                "id" => 2296,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 18:35:23"
            ],


            [
                "id" => 2297,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 09:05:23"
            ],
            [
                "id" => 2298,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 18:35:23"
            ],


            [
                "id" => 2299,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 09:05:00"
            ],
            [
                "id" => 2300,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 18:35:00"
            ],

            // 10/03/2025
            [
                "id" => 2301,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 09:10:23"
            ],
            [
                "id" => 2302,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 13:05:23"
            ],
            [
                "id" => 2303,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 14:05:23"
            ],
            [
                "id" => 2304,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 18:35:23"
            ],


            [
                "id" => 2305,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 09:05:23"
            ],
            [
                "id" => 2306,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 18:35:23"
            ],


            [
                "id" => 2307,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 09:05:00"
            ],
            [
                "id" => 2308,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 18:35:00"
            ],


            // 10/06/2025
            [
                "id" => 2309,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 09:10:23"
            ],
            [
                "id" => 2310,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 13:05:23"
            ],
            [
                "id" => 2311,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 14:05:23"
            ],
            [
                "id" => 2312,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 18:35:23"
            ],


            [
                "id" => 2313,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 09:05:23"
            ],
            [
                "id" => 2314,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 18:35:23"
            ],


            [
                "id" => 2315,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 09:05:00"
            ],
            [
                "id" => 2316,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 18:35:00"
            ],


            // 10/07/2025
            [
                "id" => 2317,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 09:10:23"
            ],
            [
                "id" => 2318,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 13:05:23"
            ],
            [
                "id" => 2319,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 14:05:23"
            ],
            [
                "id" => 2320,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 18:35:23"
            ],


            [
                "id" => 2321,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 09:05:23"
            ],
            [
                "id" => 2322,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 18:35:23"
            ],


            [
                "id" => 2323,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 09:05:00"
            ],
            [
                "id" => 2324,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 18:35:00"
            ],

            // 10/08/2025
            [
                "id" => 2325,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 09:10:23"
            ],
            [
                "id" => 2326,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 13:05:23"
            ],
            [
                "id" => 2327,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 14:05:23"
            ],
            [
                "id" => 2328,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 18:35:23"
            ],


            [
                "id" => 2329,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 09:05:23"
            ],
            [
                "id" => 2330,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 18:35:23"
            ],


            [
                "id" => 2331,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 09:05:00"
            ],
            [
                "id" => 2332,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 18:35:00"
            ],


            // 10/09/2025
            [
                "id" => 2333,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 09:10:23"
            ],
            [
                "id" => 2334,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 13:05:23"
            ],
            [
                "id" => 2335,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 14:05:23"
            ],
            [
                "id" => 2336,
                "emp" => 10,
                "emp_code" => "201",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 18:35:23"
            ],


            [
                "id" => 2337,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 09:05:23"
            ],
            [
                "id" => 2338,
                "emp" => 10,
                "emp_code" => "202",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 18:35:23"
            ],


            [
                "id" => 2339,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 09:05:00"
            ],
            [
                "id" => 2340,
                "emp" => 10,
                "emp_code" => "203",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 18:35:00"
            ],
        ];

        return $data;
    }

    public function getNonSaasDemoData()
    {
        $data = [

            // 10/01/2025
            [
                "id" => 2285,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 12:54:23"
            ],
            [
                "id" => 2286,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 13:05:23"
            ],
            [
                "id" => 2287,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 14:05:23"
            ],
            [
                "id" => 2288,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-01 18:35:23"
            ],


            [
                "id" => 2289,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 09:05:23"
            ],
            [
                "id" => 2290,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 18:35:23"
            ],


            [
                "id" => 2291,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 09:05:00"
            ],
            [
                "id" => 2292,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-01 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-01 18:35:00"
            ],

            // 10/02/2025
            [
                "id" => 2293,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 09:10:23"
            ],
            [
                "id" => 2294,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 13:05:23"
            ],
            [
                "id" => 2295,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 14:05:23"
            ],
            [
                "id" => 2296,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-02 18:35:23"
            ],


            [
                "id" => 2297,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 09:05:23"
            ],
            [
                "id" => 2298,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 18:35:23"
            ],


            [
                "id" => 2299,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 09:05:00"
            ],
            [
                "id" => 2300,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-02 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-02 18:35:00"
            ],

            // 10/03/2025
            [
                "id" => 2301,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 09:10:23"
            ],
            [
                "id" => 2302,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 13:05:23"
            ],
            [
                "id" => 2303,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 14:05:23"
            ],
            [
                "id" => 2304,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-03 18:35:23"
            ],


            [
                "id" => 2305,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 09:05:23"
            ],
            [
                "id" => 2306,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 18:35:23"
            ],


            [
                "id" => 2307,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 09:05:00"
            ],
            [
                "id" => 2308,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-03 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-03 18:35:00"
            ],


            // 10/06/2025
            [
                "id" => 2309,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 09:10:23"
            ],
            [
                "id" => 2310,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 13:05:23"
            ],
            [
                "id" => 2311,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 14:05:23"
            ],
            [
                "id" => 2312,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-06 18:35:23"
            ],


            [
                "id" => 2313,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 09:05:23"
            ],
            [
                "id" => 2314,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 18:35:23"
            ],


            [
                "id" => 2315,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 09:05:00"
            ],
            [
                "id" => 2316,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-06 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-06 18:35:00"
            ],


            // 10/07/2025
            [
                "id" => 2317,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 09:10:23"
            ],
            [
                "id" => 2318,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 13:05:23"
            ],
            [
                "id" => 2319,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 14:05:23"
            ],
            [
                "id" => 2320,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-07 18:35:23"
            ],


            [
                "id" => 2321,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 09:05:23"
            ],
            [
                "id" => 2322,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 18:35:23"
            ],


            [
                "id" => 2323,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 09:05:00"
            ],
            [
                "id" => 2324,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-07 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-07 18:35:00"
            ],

            // 10/08/2025
            [
                "id" => 2325,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 09:10:23"
            ],
            [
                "id" => 2326,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 13:05:23"
            ],
            [
                "id" => 2327,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 14:05:23"
            ],
            [
                "id" => 2328,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-08 18:35:23"
            ],


            [
                "id" => 2329,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 09:05:23"
            ],
            [
                "id" => 2330,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 18:35:23"
            ],


            [
                "id" => 2331,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 09:05:00"
            ],
            [
                "id" => 2332,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-08 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-08 18:35:00"
            ],


            // 10/09/2025
            [
                "id" => 2333,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 09:00:17",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 09:10:23"
            ],
            [
                "id" => 2334,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 13:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 13:05:23"
            ],
            [
                "id" => 2335,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 14:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 14:05:23"
            ],
            [
                "id" => 2336,
                "emp" => 10,
                "emp_code" => "101",
                "first_name" => "Mrs. Elvie Towne IV",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2024-10-09 18:35:23"
            ],


            [
                "id" => 2337,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 09:05:23"
            ],
            [
                "id" => 2338,
                "emp" => 10,
                "emp_code" => "102",
                "first_name" => "Effie Wilderman",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 18:35:23"
            ],


            [
                "id" => 2339,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 09:00:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock In",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 09:05:00"
            ],
            [
                "id" => 2340,
                "emp" => 10,
                "emp_code" => "103",
                "first_name" => "Tomasa Mitchell",
                "last_name" => null,
                "department" => "Support",
                "position" => "Technical Support",
                "punch_time" => "2025-10-09 18:30:00",
                "punch_state" => "255",
                "punch_state_display" => "Clock Out",
                "verify_type" => 1,
                "verify_type_display" => "Fingerprint",
                "work_code" => "",
                "gps_location" => null,
                "area_alias" => "Operation Office",
                "terminal_sn" => "COAW221061101",
                "temperature" => 0.0,
                "is_mask" => "-",
                "terminal_alias" => "F18/ID",
                "upload_time" => "2025-10-09 18:35:00"
            ],
        ];

        return $data;
    }
}
