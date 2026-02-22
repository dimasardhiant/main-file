<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDepreciation;
use App\Models\AssetMaintenance;
use App\Models\AssetType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asset::withPermissionCheck()->with(['assetType', 'currentAssignment.employee']);

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('serial_number', 'like', '%' . $request->search . '%')
                    ->orWhere('asset_code', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('location', 'like', '%' . $request->search . '%')
                    ->orWhere('supplier', 'like', '%' . $request->search . '%');
            });
        }

        // Handle asset type filter
        if ($request->has('asset_type_id') && !empty($request->asset_type_id)) {
            $query->where('asset_type_id', $request->asset_type_id);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Handle condition filter
        if ($request->has('condition') && !empty($request->condition)) {
            $query->where('condition', $request->condition);
        }

        // Handle location filter
        if ($request->has('location') && !empty($request->location)) {
            $query->where('location', $request->location);
        }

        // Handle purchase date range filter
        if ($request->has('purchase_date_from') && !empty($request->purchase_date_from)) {
            $query->whereDate('purchase_date', '>=', $request->purchase_date_from);
        }
        if ($request->has('purchase_date_to') && !empty($request->purchase_date_to)) {
            $query->whereDate('purchase_date', '<=', $request->purchase_date_to);
        }

        // Handle sorting
        if ($request->has('sort_field') && !empty($request->sort_field)) {
            $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $assets = $query->paginate($request->per_page ?? 10);

        // Get asset types for filter dropdown
        $assetTypes = AssetType::whereIn('created_by', getCompanyAndUsersId())
            ->select('id', 'name')
            ->get();

        // Get unique locations for filter dropdown
        $locations = Asset::whereIn('created_by', getCompanyAndUsersId())
            ->select('location')
            ->distinct()
            ->whereNotNull('location')
            ->pluck('location')
            ->toArray();

        // Get employees for assignment dropdown
        $employees = User::with('employee')
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? ''
                ];
            });

        return Inertia::render('hr/assets/index', [
            'assets' => $assets,
            'assetTypes' => $assetTypes,
            'locations' => $locations,
            'employees' => $employees,
            'filters' => $request->all(['search', 'asset_type_id', 'status', 'condition', 'location', 'purchase_date_from', 'purchase_date_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Display the dashboard view.
     */
    public function dashboard(Request $request)
    {
        // Get asset counts by status
        $assetCounts = [
            'total' => Asset::whereIn('created_by', getCompanyAndUsersId())->count(),
            'available' => Asset::whereIn('created_by', getCompanyAndUsersId())->where('status', 'available')->count(),
            'assigned' => Asset::whereIn('created_by', getCompanyAndUsersId())->where('status', 'assigned')->count(),
            'under_maintenance' => Asset::whereIn('created_by', getCompanyAndUsersId())->where('status', 'under_maintenance')->count(),
            'disposed' => Asset::whereIn('created_by', getCompanyAndUsersId())->where('status', 'disposed')->count(),
        ];

        // Get asset counts by type
        $assetTypeData = AssetType::whereIn('created_by', getCompanyAndUsersId())
            ->withCount('assets')
            ->get()
            ->map(function ($type) {
                return [
                    'name' => $type->name,
                    'count' => $type->assets_count
                ];
            });

        // Get recent assignments
        $recentAssignments = AssetAssignment::with(['asset', 'employee'])
            ->whereHas('asset', function ($q) {
                $q->whereIn('created_by', getCompanyAndUsersId());
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get upcoming maintenance
        $upcomingMaintenance = AssetMaintenance::with('asset')
            ->whereHas('asset', function ($q) {
                $q->whereIn('created_by', getCompanyAndUsersId());
            })
            ->where('status', 'scheduled')
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get();

        // Get assets with expiring warranties
        $expiringWarranties = Asset::whereIn('created_by', getCompanyAndUsersId())
            ->whereNotNull('warranty_expiry_date')
            ->where('warranty_expiry_date', '>=', now())
            ->where('warranty_expiry_date', '<=', now()->addMonths(3))
            ->orderBy('warranty_expiry_date', 'asc')
            ->take(5)
            ->get();

        // Get asset value summary
        $assetValueSummary = [
            'total_purchase_value' => Asset::whereIn('created_by', getCompanyAndUsersId())->sum('purchase_cost'),
            'total_current_value' => AssetDepreciation::whereHas('asset', function ($q) {
                $q->whereIn('created_by', getCompanyAndUsersId());
            })->sum('current_value'),
            'total_depreciation' => Asset::whereIn('created_by', getCompanyAndUsersId())->sum('purchase_cost') - 
                AssetDepreciation::whereHas('asset', function ($q) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                })->sum('current_value'),
        ];

        return Inertia::render('hr/assets/dashboard', [
            'assetCounts' => $assetCounts,
            'assetTypeData' => $assetTypeData,
            'recentAssignments' => $recentAssignments,
            'upcomingMaintenance' => $upcomingMaintenance,
            'expiringWarranties' => $expiringWarranties,
            'assetValueSummary' => $assetValueSummary,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'asset_type_id' => 'required|exists:asset_types,id',
            'serial_number' => 'nullable|string|max:255',
            'asset_code' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:available,assigned,under_maintenance,disposed',
            'condition' => 'nullable|string|in:new,good,fair,poor',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'warranty_info' => 'nullable|string|max:255',
            'warranty_expiry_date' => 'nullable|date',
            'images' => 'nullable|string',
            'documents' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if asset type belongs to current company
        $assetType = AssetType::find($request->asset_type_id);
        if (!$assetType || !in_array($assetType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid asset type selected');
        }

        $assetData = [
            'name' => $request->name,
            'asset_type_id' => $request->asset_type_id,
            'serial_number' => $request->serial_number,
            'asset_code' => $request->asset_code,
            'purchase_date' => $request->purchase_date,
            'purchase_cost' => $request->purchase_cost,
            'status' => $request->status,
            'condition' => $request->condition,
            'description' => $request->description,
            'location' => $request->location,
            'supplier' => $request->supplier,
            'warranty_info' => $request->warranty_info,
            'warranty_expiry_date' => $request->warranty_expiry_date,
            'created_by' => creatorId(),
        ];

        // Handle image from media library
        if ($request->has('images')) {
            $assetData['images'] = $request->images;
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $assetData['documents'] = $request->documents;
        }

        $asset = Asset::create($assetData);

        // Generate QR code
        // $qrCodeContent = json_encode([
        //     'id' => $asset->id,
        //     'name' => $asset->name,
        //     'asset_code' => $asset->asset_code,
        //     'serial_number' => $asset->serial_number,
        //     'type' => $assetType->name,
        // ]);
        
        // $qrCodePath = 'assets/qrcodes/' . $asset->id . '.png';
        // $qrCode = QrCode::format('png')
        //     ->size(200)
        //     ->generate($qrCodeContent);
        
        // Storage::disk('public')->put($qrCodePath, $qrCode);
        // $asset->update(['qr_code' => $qrCodePath]);

        // Create depreciation record if purchase cost and date are provided
        if ($request->has('depreciation_method') && $request->purchase_cost && $request->purchase_date) {
            AssetDepreciation::create([
                'asset_id' => $asset->id,
                'method' => $request->depreciation_method,
                'useful_life_years' => $request->useful_life_years ?? 5,
                'salvage_value' => $request->salvage_value ?? ($request->purchase_cost * 0.1), // Default to 10% of purchase cost
                'current_value' => $request->purchase_cost,
                'last_calculated_date' => now(),
                'created_by' => creatorId(),
            ]);
        }

        return redirect()->back()->with('success', __('Asset created successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to view this asset'));
        }

        // Load relationships
        $asset->load([
            'assetType',
            'assignments.employee',
            'assignments.assigner',
            'assignments.receiver',
            'maintenances',
            'depreciation',
            'currentAssignment.employee'
        ]);
        // dd($asset->toArray());

        // Get asset types for form dropdown
        $assetTypes = AssetType::whereIn('created_by', getCompanyAndUsersId())
            ->select('id', 'name')
            ->get();

        // Get employees for assignment dropdown
        $employees = User::with('employee')
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? ''
                ];
            });

        return Inertia::render('hr/assets/show', [
            'asset' => $asset,
            'assetTypes' => $assetTypes,
            'employees' => $employees,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'You do not have permission to update this asset');
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'asset_type_id' => 'required|exists:asset_types,id',
            'serial_number' => 'nullable|string|max:255',
            'asset_code' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:available,assigned,under_maintenance,disposed',
            'condition' => 'nullable|string|in:new,good,fair,poor',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'warranty_info' => 'nullable|string|max:255',
            'warranty_expiry_date' => 'nullable|date',
            'images' => 'nullable|string',
            'documents' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if asset type belongs to current company
        $assetType = AssetType::find($request->asset_type_id);
        if (!$assetType || !in_array($assetType->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', 'Invalid asset type selected');
        }

        $assetData = [
            'name' => $request->name,
            'asset_type_id' => $request->asset_type_id,
            'serial_number' => $request->serial_number,
            'asset_code' => $request->asset_code,
            'purchase_date' => $request->purchase_date,
            'purchase_cost' => $request->purchase_cost,
            'status' => $request->status,
            'condition' => $request->condition,
            'description' => $request->description,
            'location' => $request->location,
            'supplier' => $request->supplier,
            'warranty_info' => $request->warranty_info,
            'warranty_expiry_date' => $request->warranty_expiry_date,
        ];

        // Handle image from media library
        if ($request->has('images')) {
            $assetData['images'] = $request->images;
        }

        // Handle document from media library
        if ($request->has('documents')) {
            $assetData['documents'] = $request->documents;
        }

        $asset->update($assetData);

        // Update or create depreciation record if purchase cost and date are provided
        if ($request->has('depreciation_method') && $request->purchase_cost && $request->purchase_date) {
            $depreciation = $asset->depreciation;
            
            if ($depreciation) {
                $depreciation->update([
                    'method' => $request->depreciation_method,
                    'useful_life_years' => $request->useful_life_years ?? $depreciation->useful_life_years,
                    'salvage_value' => $request->salvage_value ?? $depreciation->salvage_value,
                    'last_calculated_date' => now(),
                ]);
                
                // Recalculate current value
                $depreciation->updateCurrentValue();
            } else {
                AssetDepreciation::create([
                    'asset_id' => $asset->id,
                    'method' => $request->depreciation_method,
                    'useful_life_years' => $request->useful_life_years ?? 5,
                    'salvage_value' => $request->salvage_value ?? ($request->purchase_cost * 0.1), // Default to 10% of purchase cost
                    'current_value' => $request->purchase_cost,
                    'last_calculated_date' => now(),
                    'created_by' => creatorId(),
                ]);
            }
        }

        return redirect()->back()->with('success', __('Asset updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this asset'));
        }

        // Check if asset is currently assigned
        if ($asset->status === 'assigned') {
            return redirect()->back()->with('error', __('Cannot delete an asset that is currently assigned'));
        }

        // Delete associated files
        if ($asset->images) {
            Storage::disk('public')->delete($asset->images);
        }
        if ($asset->documents) {
            Storage::disk('public')->delete($asset->documents);
        }
        if ($asset->qr_code) {
            Storage::disk('public')->delete($asset->qr_code);
        }

        // Delete associated records
        $asset->assignments()->delete();
        $asset->maintenances()->delete();
        if ($asset->depreciation) {
            $asset->depreciation->delete();
        }

        $asset->delete();

        return redirect()->back()->with('success', __('Asset deleted successfully'));
    }

    /**
     * Assign asset to an employee.
     */
    public function assign(Request $request, Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to assign this asset'));
        }

        // Check if asset is available
        if ($asset->status !== 'available') {
            return redirect()->back()->with('error', __('Only available assets can be assigned'));
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'checkout_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:checkout_date',
            'checkout_condition' => 'nullable|string|in:new,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if employee belongs to current company
        $user = User::where('id', $request->employee_id)
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();
        if (!$user) {
            return redirect()->back()->with('error', __('Invalid employee selected'));
        }

        // Create assignment
        AssetAssignment::create([
            'asset_id' => $asset->id,
            'employee_id' => $request->employee_id,
            'checkout_date' => $request->checkout_date,
            'expected_return_date' => $request->expected_return_date,
            'checkout_condition' => $request->checkout_condition ?? $asset->condition,
            'notes' => $request->notes,
            'assigned_by' => auth()->id(),
        ]);

        // Update asset status
        $asset->update([
            'status' => 'assigned',
        ]);

        return redirect()->back()->with('success', __('Asset assigned successfully'));
    }

    /**
     * Return an assigned asset.
     */
    public function returnAsset(Request $request, Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to return this asset'));
        }

        // Check if asset is assigned
        if ($asset->status !== 'assigned') {
            return redirect()->back()->with('error', __('Only assigned assets can be returned'));
        }

        $validator = Validator::make($request->all(), [
            'checkin_date' => 'required|date',
            'checkin_condition' => 'nullable|string|in:new,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get current assignment
        $assignment = $asset->currentAssignment;
        if (!$assignment) {
            return redirect()->back()->with('error', __('No active assignment found for this asset'));
        }

        // Update assignment
        $assignment->update([
            'checkin_date' => $request->checkin_date,
            'checkin_condition' => $request->checkin_condition ?? $asset->condition,
            'notes' => $assignment->notes . "\n\nReturn notes: " . ($request->notes ?? 'No notes provided.'),
            'received_by' => auth()->id(),
        ]);

        // Update asset status and condition
        $asset->update([
            'status' => 'available',
            'condition' => $request->checkin_condition ?? $asset->condition,
        ]);

        return redirect()->back()->with('success', __('Asset returned successfully'));
    }

    /**
     * Schedule maintenance for an asset.
     */
    public function scheduleMaintenance(Request $request, Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to schedule maintenance for this asset'));
        }

        $validator = Validator::make($request->all(), [
            'maintenance_type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cost' => 'nullable|numeric|min:0',
            'details' => 'nullable|string',
            'supplier' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Create maintenance record
        AssetMaintenance::create([
            'asset_id' => $asset->id,
            'maintenance_type' => $request->maintenance_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'cost' => $request->cost,
            'status' => 'scheduled',
            'details' => $request->details,
            'supplier' => $request->supplier,
            'created_by' => auth()->id(),
        ]);

        // Update asset status if maintenance is starting today or has already started
        if ($request->start_date <= now()->format('Y-m-d')) {
            $asset->update([
                'status' => 'under_maintenance',
            ]);
        }

        return redirect()->back()->with('success', __('Maintenance scheduled successfully'));
    }

    /**
     * Update maintenance status.
     */
    public function updateMaintenance(Request $request, AssetMaintenance $maintenance)
    {
        // Check if maintenance belongs to current company
        $asset = $maintenance->asset;
        if (!$asset || !in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this maintenance record'));
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled',
            'end_date' => 'nullable|date|after_or_equal:' . $maintenance->start_date,
            'completion_notes' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update maintenance record
        $maintenance->update([
            'status' => $request->status,
            'end_date' => $request->end_date,
            'completion_notes' => $request->completion_notes,
            'cost' => $request->cost ?? $maintenance->cost,
        ]);

        // Update asset status based on maintenance status
        if (in_array($request->status, ['completed', 'cancelled'])) {
            $asset->update([
                'status' => 'available',
            ]);
        } elseif (in_array($request->status, ['scheduled', 'in_progress'])) {
            if ($request->status === 'in_progress' || $maintenance->start_date <= now()->format('Y-m-d')) {
                $asset->update([
                    'status' => 'under_maintenance',
                ]);
            }
        }

        return redirect()->back()->with('success', __('Maintenance record updated successfully'));
    }

    /**
     * Download document file.
     */
    public function downloadDocument(Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to access this document'));
        }

        if (!$asset->documents) {
            return redirect()->back()->with('error', __('Document file not found'));
        }

        // Handle cloud storage URLs (already full URLs)
        if (filter_var($asset->documents, FILTER_VALIDATE_URL)) {
            return Storage::download($asset->documents);
        }

        // Handle local storage paths
        $relativePath = str_replace('/Product/hrmgo-saas-react/storage/', '', $asset->documents);
        
        if (!Storage::exists($relativePath)) {
            return redirect()->back()->with('error', __('Document file not found'));
        }

        return Storage::download($relativePath);
    }

    /**
     * View image file.
     */
    public function viewImage(Asset $asset)
    {
        // Check if asset belongs to current company
        if (!in_array($asset->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to access this image'));
        }

        if (!$asset->images) {
            return redirect()->back()->with('error', __('Image file not found'));
        }

        // Handle cloud storage URLs (already full URLs)
        if (filter_var($asset->images, FILTER_VALIDATE_URL)) {
            return redirect($asset->images);
        }

        // Handle local storage paths
        $relativePath = str_replace('/Product/hrmgo-saas-react/storage/', '', $asset->images);
        
        if (!Storage::exists($relativePath)) {
            return redirect()->back()->with('error', __('Image file not found'));
        }

        return Storage::response($relativePath);
    }



    /**
     * Generate depreciation report.
     */
    public function depreciationReport(Request $request)
    {
        $query = Asset::with('depreciation')
            ->whereHas('depreciation')
            ->whereIn('created_by', getCompanyAndUsersId());

        // Handle asset type filter
        if ($request->has('asset_type_id') && !empty($request->asset_type_id)) {
            $query->where('asset_type_id', $request->asset_type_id);
        }

        // Handle purchase date range filter
        if ($request->has('purchase_date_from') && !empty($request->purchase_date_from)) {
            $query->whereDate('purchase_date', '>=', $request->purchase_date_from);
        }
        if ($request->has('purchase_date_to') && !empty($request->purchase_date_to)) {
            $query->whereDate('purchase_date', '<=', $request->purchase_date_to);
        }

        // Handle sorting
        if ($request->has('sort_field') && !empty($request->sort_field)) {
            $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
        } else {
            $query->orderBy('purchase_date', 'desc');
        }

        $assets = $query->paginate($request->per_page ?? 10);

        // Get asset types for filter dropdown
        $assetTypes = AssetType::whereIn('created_by', getCompanyAndUsersId())
            ->select('id', 'name')
            ->get();

        // Calculate totals
        $totalPurchaseValue = $assets->sum('purchase_cost');
        $totalCurrentValue = 0;
        $totalDepreciation = 0;

        foreach ($assets as $asset) {
            if ($asset->depreciation) {
                $totalCurrentValue += $asset->depreciation->current_value;
                $totalDepreciation += $asset->purchase_cost - $asset->depreciation->current_value;
            }
        }

        return Inertia::render('hr/assets/depreciation-report', [
            'assets' => $assets,
            'assetTypes' => $assetTypes,
            'totalPurchaseValue' => $totalPurchaseValue,
            'totalCurrentValue' => $totalCurrentValue,
            'totalDepreciation' => $totalDepreciation,
            'filters' => $request->all(['asset_type_id', 'purchase_date_from', 'purchase_date_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Export depreciation report to CSV.
     */
    public function exportDepreciationCsv(Request $request)
    {
        $query = Asset::with(['assetType', 'depreciation'])
            ->whereHas('depreciation')
            ->whereIn('created_by', getCompanyAndUsersId());

        // Apply same filters as report
        if ($request->has('asset_type_id') && !empty($request->asset_type_id)) {
            $query->where('asset_type_id', $request->asset_type_id);
        }

        if ($request->has('purchase_date_from') && !empty($request->purchase_date_from)) {
            $query->whereDate('purchase_date', '>=', $request->purchase_date_from);
        }
        if ($request->has('purchase_date_to') && !empty($request->purchase_date_to)) {
            $query->whereDate('purchase_date', '<=', $request->purchase_date_to);
        }

        $assets = $query->orderBy('purchase_date', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Asset Name', 'Asset Type', 'Purchase Date', 'Purchase Cost', 'Current Value', 'Depreciation', 'Depreciation Method', 'Useful Life (Years)'];

        foreach ($assets as $asset) {
            $depreciation = $asset->depreciation;
            $csvData[] = [
                $asset->name,
                $asset->assetType->name ?? '',
                $asset->purchase_date ? date('Y-m-d', strtotime($asset->purchase_date)) : '',
                number_format($asset->purchase_cost, 2),
                number_format($depreciation->current_value ?? 0, 2),
                number_format(($asset->purchase_cost - ($depreciation->current_value ?? 0)), 2),
                ucfirst(str_replace('_', ' ', $depreciation->method ?? '')),
                $depreciation->useful_life_years ?? ''
            ];
        }

        $filename = 'depreciation-report-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


}