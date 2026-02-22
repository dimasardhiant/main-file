<?php

namespace App\Http\Controllers;

use App\Models\DocumentAcknowledgment;
use App\Models\HrDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DocumentAcknowledgmentController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-document-acknowledgments')) {
            $query = DocumentAcknowledgment::with(['document', 'user', 'assignedBy'])->where(function ($q) {
                if (Auth::user()->can('manage-any-document-acknowledgments')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-document-acknowledgments')) {
                    $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id())->orWhere('assigned_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('document', function ($dq) use ($request) {
                        $dq->where('title', 'like', '%' . $request->search . '%');
                    })
                        ->orWhereHas('user', function ($uq) use ($request) {
                            $uq->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            }

            if ($request->has('document_id') && !empty($request->document_id) && $request->document_id !== 'all') {
                $query->where('document_id', $request->document_id);
            }

            if ($request->has('user_id') && !empty($request->user_id) && $request->user_id !== 'all') {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Auto-update overdue acknowledgments
            DocumentAcknowledgment::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'Pending')
                ->where('due_date', '<', Carbon::today())
                ->update(['status' => 'Overdue']);

            $query->orderBy('id', 'desc');
            $documentAcknowledgments = $query->paginate($request->per_page ?? 10);

            $documents = HrDocument::whereIn('created_by', getCompanyAndUsersId())
                ->where('requires_acknowledgment', true)
                ->select('id', 'title')
                ->get();

            $users = User::whereIn('created_by', getCompanyAndUsersId())
                ->where('type', 'employee')
                ->select('id', 'name')
                ->get();

            return Inertia::render('hr/documents/document-acknowledgments/index', [
                'documentAcknowledgments' => $documentAcknowledgments,
                'documents' => $documents,
                'users' => $users,
                'filters' => $request->all(['search', 'document_id', 'user_id', 'status', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:hr_documents,id',
            'user_id' => 'required|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'acknowledgment_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if acknowledgment already exists
        $existing = DocumentAcknowledgment::where('document_id', $request->document_id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', __('Acknowledgment already exists for this user and document'));
        }

        DocumentAcknowledgment::create([
            'document_id' => $request->document_id,
            'user_id' => $request->user_id,
            'due_date' => $request->due_date ?? Carbon::now()->addDays(7),
            'acknowledgment_note' => $request->acknowledgment_note,
            'assigned_by' => creatorId(),
            'assigned_at' => now(),
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Document acknowledgment assigned successfully'));
    }

    public function update(Request $request, DocumentAcknowledgment $documentAcknowledgment)
    {
        if (!in_array($documentAcknowledgment->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this acknowledgment'));
        }

        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:hr_documents,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:Pending,Acknowledged,Overdue,Exempted',
            'due_date' => 'nullable|date',
            'acknowledgment_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        // Check if acknowledgment already exists for different document/user combination
        if ($request->document_id != $documentAcknowledgment->document_id || $request->user_id != $documentAcknowledgment->user_id) {
            $existing = DocumentAcknowledgment::where('document_id', $request->document_id)
                ->where('user_id', $request->user_id)
                ->where('id', '!=', $documentAcknowledgment->id)
                ->first();

            if ($existing) {
                return redirect()->back()->with('error', __('Acknowledgment already exists for this user and document'));
            }
        }

        $updateData = [
            'document_id' => $request->document_id,
            'user_id' => $request->user_id,
            'status' => $request->status,
            'due_date' => $request->due_date,
            'acknowledgment_note' => $request->acknowledgment_note,
        ];

        if ($request->status === 'Acknowledged' && !$documentAcknowledgment->acknowledged_at) {
            $updateData['acknowledged_at'] = now();
            $updateData['ip_address'] = $request->ip();
            $updateData['user_agent'] = $request->userAgent();
        }

        $documentAcknowledgment->update($updateData);

        return redirect()->back()->with('success', __('Document acknowledgment updated successfully'));
    }

    public function destroy(DocumentAcknowledgment $documentAcknowledgment)
    {
        if (!in_array($documentAcknowledgment->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this acknowledgment'));
        }

        $documentAcknowledgment->delete();
        return redirect()->back()->with('success', __('Document acknowledgment deleted successfully'));
    }

    public function acknowledge(Request $request, DocumentAcknowledgment $documentAcknowledgment)
    {
        if (!in_array($documentAcknowledgment->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to acknowledge this document'));
        }

        $validator = Validator::make($request->all(), [
            'acknowledgment_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $documentAcknowledgment->update([
            'status' => 'Acknowledged',
            'acknowledged_at' => now(),
            'acknowledgment_note' => $request->acknowledgment_note ?? 'Document acknowledged',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', __('Document acknowledged successfully'));
    }

    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:hr_documents,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $assignedCount = 0;
        $dueDate = $request->due_date ?? Carbon::now()->addDays(7);

        foreach ($request->user_ids as $userId) {
            // Check if acknowledgment already exists
            $existing = DocumentAcknowledgment::where('document_id', $request->document_id)
                ->where('user_id', $userId)
                ->first();

            if (!$existing) {
                DocumentAcknowledgment::create([
                    'document_id' => $request->document_id,
                    'user_id' => $userId,
                    'due_date' => $dueDate,
                    'assigned_by' => creatorId(),
                    'assigned_at' => now(),
                    'created_by' => creatorId(),
                ]);
                $assignedCount++;
            }
        }

        return redirect()->back()->with('success', __('Document assigned to :count users successfully', ['count' => $assignedCount]));
    }
}
