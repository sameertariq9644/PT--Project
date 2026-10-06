<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Support\TemplateRenderer;
use Illuminate\Support\Facades\Mail;

class SendLeadVideo
{
    /**
     * Email the lead the "lead video" template and mark them as eligible.
     */
    public function handle(Lead $lead): Lead
    {
        Mail::to($lead->email)->queue(
            TemplatedMail::forKey(EmailTemplate::LeadVideo, TemplateRenderer::variables($lead)),
        );

        $lead->video_sent_at = now();

        if ($lead->status === LeadStatus::NeedsReview) {
            $lead->status = LeadStatus::Eligible;
        }

        $lead->save();

        return $lead;
    }
}
