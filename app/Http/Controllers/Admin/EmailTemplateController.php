<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewEmailTemplateRequest;
use App\Http\Requests\Admin\SaveEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Support\DefaultEmailTemplates;
use App\Support\TemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        // Make sure every system template exists, even on a database that was never seeded.
        foreach (array_keys(DefaultEmailTemplates::all()) as $key) {
            DefaultEmailTemplates::resolve($key);
        }

        return view('admin.email-templates', [
            'templates' => EmailTemplate::query()->orderByDesc('is_system')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.email-template-form', [
            'template' => new EmailTemplate,
            'placeholders' => $this->placeholders(),
        ]);
    }

    public function store(SaveEmailTemplateRequest $request): RedirectResponse
    {
        EmailTemplate::query()->create($request->validated());

        return redirect()->route('admin.email-templates')->with('status', 'Template created.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-template-form', [
            'template' => $emailTemplate,
            'placeholders' => $this->placeholders(),
        ]);
    }

    public function update(SaveEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($request->validated());

        return redirect()->route('admin.email-templates')->with('status', '“'.$emailTemplate->name.'” saved.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        if ($emailTemplate->is_system) {
            return back()->withErrors(['template' => 'System templates are used by the site and cannot be deleted.']);
        }

        $emailTemplate->delete();

        return redirect()->route('admin.email-templates')->with('status', 'Template deleted.');
    }

    /**
     * Render the editor's current text with sample values for the live preview.
     */
    public function preview(PreviewEmailTemplateRequest $request, TemplateRenderer $renderer): JsonResponse
    {
        $rendered = $renderer->render(
            (string) $request->validated('subject'),
            (string) $request->validated('body'),
            TemplateRenderer::sampleVariables(),
        );

        return response()->json([
            'subject' => $rendered['subject'],
            'html' => view('emails.templated', ['html' => $rendered['html']])->render(),
        ]);
    }

    /**
     * The insertable tokens for the editor's chips, e.g. "{{name}}" => description.
     *
     * @return array<string, string>
     */
    protected function placeholders(): array
    {
        $tokens = [];

        foreach (TemplateRenderer::PLACEHOLDERS as $key => $description) {
            $tokens['{{'.$key.'}}'] = $description;
        }

        return $tokens;
    }
}
