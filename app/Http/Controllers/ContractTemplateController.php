<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ContractTemplateController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-contract-templates')) {
            $query = ContractTemplate::with(['contractType'])->where(function ($q) {
                if (Auth::user()->can('manage-any-contract-templates')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-contract-templates')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('contract_type_id') && !empty($request->contract_type_id) && $request->contract_type_id !== 'all') {
                $query->where('contract_type_id', $request->contract_type_id);
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('is_default') && $request->is_default !== 'all') {
                $query->where('is_default', $request->is_default === 'true');
            }

            $query->orderBy('is_default', 'desc')->orderBy('id', 'desc');
            $contractTemplates = $query->paginate($request->per_page ?? 10);

            $contractTypes = ContractType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();

            return Inertia::render('hr/contracts/contract-templates/index', [
                'contractTemplates' => $contractTemplates,
                'contractTypes' => $contractTypes,
                'filters' => $request->all(['search', 'contract_type_id', 'status', 'is_default', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contract_type_id' => 'required|exists:contract_types,id',
            'template_content' => 'required|string',
            'variables' => 'nullable|array',
            'clauses' => 'nullable|array',
            'is_default' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default for this type, unset other defaults
        if ($request->boolean('is_default')) {
            ContractTemplate::whereIn('created_by', getCompanyAndUsersId())
                ->where('contract_type_id', $request->contract_type_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        ContractTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'contract_type_id' => $request->contract_type_id,
            'template_content' => $request->template_content,
            'variables' => $request->variables,
            'clauses' => $request->clauses,
            'is_default' => $request->boolean('is_default'),
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Contract template created successfully'));
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        if (!in_array($contractTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this template'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contract_type_id' => 'required|exists:contract_types,id',
            'template_content' => 'required|string',
            'variables' => 'nullable|array',
            'clauses' => 'nullable|array',
            'is_default' => 'boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default for this type, unset other defaults
        if ($request->boolean('is_default') && !$contractTemplate->is_default) {
            ContractTemplate::whereIn('created_by', getCompanyAndUsersId())
                ->where('contract_type_id', $request->contract_type_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $contractTemplate->update([
            'name' => $request->name,
            'description' => $request->description,
            'contract_type_id' => $request->contract_type_id,
            'template_content' => $request->template_content,
            'variables' => $request->variables,
            'clauses' => $request->clauses,
            'is_default' => $request->boolean('is_default'),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Contract template updated successfully'));
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        if (!in_array($contractTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this template'));
        }

        $contractTemplate->delete();
        return redirect()->back()->with('success', __('Contract template deleted successfully'));
    }

    public function toggleStatus(ContractTemplate $contractTemplate)
    {
        if (!in_array($contractTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this template'));
        }

        $contractTemplate->update([
            'status' => $contractTemplate->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Template status updated successfully'));
    }

    public function preview(Request $request, ContractTemplate $contractTemplate)
    {
        if (!in_array($contractTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to preview this template'));
        }

        $variables = $request->get('variables', []);
        $generatedContent = $contractTemplate->generateContract($variables);

        return response()->json([
            'content' => $generatedContent,
            'variables' => $contractTemplate->variables,
        ]);
    }

    public function generate(Request $request, ContractTemplate $contractTemplate)
    {
        if (!in_array($contractTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to generate from this template'));
        }

        $validator = Validator::make($request->all(), [
            'variables' => 'required|array',
            'filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $generatedContent = $contractTemplate->generateContract($request->variables);
        $filename = $request->filename ?? ($contractTemplate->name . '_' . date('Y-m-d'));

        $html = '<div style="font-family: Arial, sans-serif; line-height: 1.6; padding: 20px;">' . nl2br($generatedContent) . '</div>';
        $pdf = Pdf::loadHTML($html);
        return $pdf->download($filename . '.pdf');
    }
}
