<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackVisitRequest;
use App\Jobs\RecordAffiliateVisitJob;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    /**
     * Receive an affiliate link click from resources/js/tracking.js. The
     * request only queues a job; workers fold it into the daily aggregates.
     */
    public function store(TrackVisitRequest $request): Response
    {
        if (! $this->isBot((string) $request->userAgent())) {
            RecordAffiliateVisitJob::dispatch(
                $request->validated('ref'),
                $request->validated('vid'),
                $request->validated('campaign'),
            );
        }

        return response()->noContent();
    }

    protected function isBot(string $userAgent): bool
    {
        return $userAgent === ''
            || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|headless|lighthouse|curl|wget|python-requests/i', $userAgent) === 1;
    }
}
