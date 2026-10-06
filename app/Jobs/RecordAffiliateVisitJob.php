<?php

namespace App\Jobs;

use App\Actions\RecordAffiliateVisit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordAffiliateVisitJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $code, public string $visitorId, public ?string $campaign = null)
    {
        $this->onQueue(config('site.queues.tracking'));
    }

    public function handle(RecordAffiliateVisit $recordVisit): void
    {
        $recordVisit->handle($this->code, $this->visitorId, $this->campaign);
    }
}
