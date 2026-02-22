<?php

namespace App\Http\Controllers;

use App\Models\OfferTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class OfferTemplateController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-offer-templates')) {
            $query = OfferTemplate::where(function ($q) {
                if (Auth::user()->can('manage-any-offer-templates')) {
                    $q->whereIn('created_by',  getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-offer-templates')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('template_content', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $query->orderBy('created_at', 'desc');
            $offerTemplates = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/recruitment/offer-templates/index', [
                'offerTemplates' => $offerTemplates,
                'filters' => $request->all(['search', 'status', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'template_content' => 'required|string',
            'variables' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        OfferTemplate::create([
            'name' => $request->name,
            'template_content' => $request->template_content,
            'variables' => $request->variables,
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Offer template created successfully'));
    }

    public function update(Request $request, OfferTemplate $offerTemplate)
    {
        if (!in_array($offerTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this template'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'template_content' => 'required|string',
            'variables' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $offerTemplate->update([
            'name' => $request->name,
            'template_content' => $request->template_content,
            'variables' => $request->variables,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Offer template updated successfully'));
    }

    public function destroy(OfferTemplate $offerTemplate)
    {
        if (!in_array($offerTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this template'));
        }

        $offerTemplate->delete();
        return redirect()->back()->with('success', __('Offer template deleted successfully'));
    }

    public function toggleStatus(OfferTemplate $offerTemplate)
    {
        if (!in_array($offerTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this template'));
        }

        $offerTemplate->update([
            'status' => $offerTemplate->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Template status updated successfully'));
    }

    public function preview(Request $request, OfferTemplate $offerTemplate)
    {
        if (!in_array($offerTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to preview this template'));
        }

        $variables = $request->get('variables', []);
        $generatedContent = $this->generateOfferContent($offerTemplate, $variables);

        return response()->json([
            'content' => $generatedContent,
            'variables' => $offerTemplate->variables,
        ]);
    }

    public function generate(Request $request, OfferTemplate $offerTemplate)
    {
        if (!in_array($offerTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to generate from this template'));
        }

        $validator = Validator::make($request->all(), [
            'variables' => 'required|array',
            'filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $generatedContent = $this->generateOfferContent($offerTemplate, $request->variables);
        $filename = $request->filename ?? ($offerTemplate->name . '_' . date('Y-m-d'));

        $html = '<div style="font-family: Arial, sans-serif; line-height: 1.6; padding: 20px;">' . nl2br($generatedContent) . '</div>';
        $pdf = Pdf::loadHTML($html);
        return $pdf->download($filename . '.pdf');
    }

    private function generateOfferContent(OfferTemplate $offerTemplate, array $variables = [])
    {
        $content = $offerTemplate->template_content;

        if ($offerTemplate->variables && is_array($offerTemplate->variables)) {
            foreach ($offerTemplate->variables as $variable) {
                $value = $variables[$variable] ?? '{{' . $variable . '}}';
                $content = str_replace('{{' . $variable . '}}', $value, $content);
            }
        }

        return $content;
    }
}
