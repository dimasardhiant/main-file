<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class DocumentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentTemplate::withPermissionCheck()->with(['category']);

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('category_id') && !empty($request->category_id) && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('is_default') && $request->is_default !== 'all') {
            $query->where('is_default', $request->is_default === 'true');
        }

        $query->orderBy('is_default', 'desc')->orderBy('created_at', 'desc');
        $documentTemplates = $query->paginate($request->per_page ?? 10);

        $categories = DocumentCategory::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        return Inertia::render('hr/documents/document-templates/index', [
            'documentTemplates' => $documentTemplates,
            'categories' => $categories,
            'filters' => $request->all(['search', 'category_id', 'status', 'is_default', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:document_categories,id',
            'template_content' => 'required|string',
            'placeholders' => 'nullable|array',
            'default_values' => 'nullable|array',
            'is_default' => 'boolean',
            'file_format' => 'nullable|string|in:pdf,doc,docx,txt',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default for this category, unset other defaults
        if ($request->boolean('is_default')) {
            DocumentTemplate::whereIn('created_by', getCompanyAndUsersId())
                ->where('category_id', $request->category_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        DocumentTemplate::create([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'template_content' => $request->template_content,
            'placeholders' => $request->placeholders,
            'default_values' => $request->default_values,
            'is_default' => $request->boolean('is_default'),
            'file_format' => $request->file_format ?? 'pdf',
            'status' => $request->status ?? 'active',
            'created_by' => creatorId(),
        ]);

        return redirect()->back()->with('success', __('Document template created successfully'));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        if (!in_array($documentTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this template'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:document_categories,id',
            'template_content' => 'required|string',
            'placeholders' => 'nullable|array',
            'default_values' => 'nullable|array',
            'is_default' => 'boolean',
            'file_format' => 'nullable|string|in:pdf,doc,docx,txt',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // If setting as default for this category, unset other defaults
        if ($request->boolean('is_default') && !$documentTemplate->is_default) {
            DocumentTemplate::whereIn('created_by', getCompanyAndUsersId())
                ->where('category_id', $request->category_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $documentTemplate->update([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'template_content' => $request->template_content,
            'placeholders' => $request->placeholders,
            'default_values' => $request->default_values,
            'is_default' => $request->boolean('is_default'),
            'file_format' => $request->file_format ?? 'pdf',
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', __('Document template updated successfully'));
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        if (!in_array($documentTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to delete this template'));
        }

        $documentTemplate->delete();
        return redirect()->back()->with('success', __('Document template deleted successfully'));
    }

    public function toggleStatus(DocumentTemplate $documentTemplate)
    {
        if (!in_array($documentTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to update this template'));
        }

        $documentTemplate->update([
            'status' => $documentTemplate->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('success', __('Template status updated successfully'));
    }

    public function preview(Request $request, DocumentTemplate $documentTemplate)
    {
        if (!in_array($documentTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to preview this template'));
        }

        $values = $request->get('values', []);
        $generatedContent = $documentTemplate->generateDocument($values);

        return response()->json([
            'content' => $generatedContent,
            'placeholders' => $documentTemplate->getPlaceholderList(),
            'default_values' => $documentTemplate->default_values,
        ]);
    }

    public function generate(Request $request, DocumentTemplate $documentTemplate)
    {
        if (!in_array($documentTemplate->created_by, getCompanyAndUsersId())) {
            return redirect()->back()->with('error', __('You do not have permission to generate from this template'));
        }

        $validator = Validator::make($request->all(), [
            'values' => 'required|array',
            'filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $generatedContent = $documentTemplate->generateDocument($request->values);
        $filename = $request->filename ?? ($documentTemplate->name . '_' . date('Y-m-d'));
        $fileFormat = $documentTemplate->file_format ?? 'txt';

        switch ($fileFormat) {
            case 'pdf':
                $html = '<div style="font-family: Arial, sans-serif; line-height: 1.6; padding: 20px;">' . nl2br($generatedContent) . '</div>';
                $pdf = Pdf::loadHTML($html);
                return $pdf->download($filename . '.pdf');
                
            case 'doc':
                
            case 'docx':
                $phpWord = new PhpWord();
                $section = $phpWord->addSection();
                
                $lines = explode("\n", $generatedContent);
                foreach ($lines as $line) {
                    if (trim($line) !== '') {
                        $section->addText($line);
                    } else {
                        $section->addTextBreak();
                    }
                }
                
                $writer = IOFactory::createWriter($phpWord, $fileFormat === 'docx' ? 'Word2007' : 'RTF');
                $tempFile = tempnam(sys_get_temp_dir(), 'document');
                $writer->save($tempFile);
                
                $contentType = $fileFormat === 'docx' 
                    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    : 'application/msword';
                
                return response()->download($tempFile, $filename . '.' . $fileFormat, [
                    'Content-Type' => $contentType
                ])->deleteFileAfterSend(true);
                    
            default: // txt
                return response($generatedContent)
                    ->header('Content-Type', 'text/plain')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
        }
    }
}