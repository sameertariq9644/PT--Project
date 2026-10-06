<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendLeadVideo;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendLeadEmailRequest;
use App\Http\Requests\Admin\UpdateLeadStatusRequest;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Support\Csv;
use App\Support\LeadForm;
use App\Support\SiteTime;
use App\Support\TemplateRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $status = LeadStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));

        $counts = Lead::query()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totals = Lead::query()->toBase()
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as recent', [now()->subDays(7)])
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as today', [SiteTime::today()->utc()])
            ->selectRaw('sum(case when video_sent_at is not null then 1 else 0 end) as video_sent')
            ->selectRaw('sum(case when video_sent_at is null and status = ? then 1 else 0 end) as video_waiting', [LeadStatus::NeedsReview->value])
            ->first();

        return view('admin.leads', [
            'leads' => $this->filtered($status, $search)->latest('id')->paginate(25)->withQueryString(),
            'columns' => LeadForm::tableColumns(),
            'statuses' => LeadStatus::cases(),
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
            'total' => (int) $counts->sum(),
            'totals' => $totals,
        ]);
    }

    public function show(Lead $lead): View
    {
        return view('admin.lead', [
            'lead' => $lead->load(['affiliate.user:id,name', 'orders' => fn ($query) => $query->latest('id')]),
            'fields' => LeadForm::answerFields(),
            'statuses' => LeadStatus::cases(),
            'templates' => EmailTemplate::query()->orderByDesc('is_system')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Download the (filtered) leads as CSV, streamed so memory stays flat.
     */
    public function export(Request $request): StreamedResponse
    {
        $status = LeadStatus::tryFrom((string) $request->query('status'));
        $fields = LeadForm::answerFields();

        $headings = ['Date', 'Name', 'Email', 'WhatsApp', ...array_column($fields, 'table_label'), 'Status', 'Video sent', 'Campaign'];

        $rows = $this->filtered($status, trim((string) $request->query('q')))
            ->lazyById(500)
            ->map(fn (Lead $lead): array => [
                SiteTime::format($lead->created_at, 'Y-m-d H:i'),
                $lead->name,
                $lead->email,
                $lead->whatsapp,
                ...array_map(fn (array $field): ?string => $lead->answer($field['key']), $fields),
                $lead->status->label(),
                $lead->video_sent_at ? SiteTime::format($lead->video_sent_at, 'Y-m-d H:i') : '',
                $lead->utm_campaign,
            ]);

        return Csv::download('leads-'.SiteTime::today()->toDateString().'.csv', $headings, $rows);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $lead->status = $request->enum('status', LeadStatus::class);
        $lead->save();

        return back()->with('status', $lead->name.' is now marked “'.$lead->status->label().'”.');
    }

    public function sendVideo(Lead $lead, SendLeadVideo $sendLeadVideo): RedirectResponse
    {
        $sendLeadVideo->handle($lead);

        return back()->with('status', 'The video email to '.$lead->name.' has been queued.');
    }

    /**
     * Send any template to the lead. Choosing the lead video template behaves
     * like the "Send video" button (a resend).
     */
    public function sendEmail(SendLeadEmailRequest $request, Lead $lead, SendLeadVideo $sendLeadVideo): RedirectResponse
    {
        $template = EmailTemplate::query()->findOrFail($request->validated('template_id'));

        if ($template->key === EmailTemplate::LeadVideo) {
            $sendLeadVideo->handle($lead);
        } else {
            Mail::to($lead->email)->queue(new TemplatedMail($template, TemplateRenderer::variables($lead)));
        }

        return back()->with('status', '“'.$template->name.'” has been queued to '.$lead->email.'.');
    }

    /**
     * @return Builder<Lead>
     */
    protected function filtered(?LeadStatus $status, string $search): Builder
    {
        return Lead::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });
    }
}
