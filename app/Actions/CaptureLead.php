<?php

namespace App\Actions;

use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use App\Support\Attribution;
use App\Support\LeadForm;
use App\Support\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CaptureLead
{
    /**
     * Store a lead form submission and notify the admin. Submitting again
     * with the same email within 24 hours updates the existing lead.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated, Attribution $attribution, ?string $ip = null): Lead
    {
        $data = LeadForm::split($validated);

        $lead = DB::transaction(function () use ($data, $attribution, $ip): Lead {
            $lead = Lead::query()
                ->where('email', $data['email'])
                ->where('created_at', '>=', now()->subDay())
                ->latest('id')
                ->first() ?? new Lead;

            $lead->fill([...$data, 'ip' => $ip]);

            if ($lead->affiliate_id === null) {
                $lead->affiliate_id = $attribution->affiliateFor($data['email'])?->id;
                $lead->utm_campaign = $lead->affiliate_id ? $attribution->campaign : null;
            }

            $lead->save();

            return $lead;
        });

        $recipients = Site::recipients('notifications.lead_recipients');

        if ($lead->wasRecentlyCreated && $recipients !== []) {
            Mail::to($recipients)->queue(new LeadReceivedMail($lead));
        }

        return $lead;
    }
}
