<?php

namespace App\Http\Controllers;

use App\Actions\CaptureLead;
use App\Http\Requests\StoreLeadRequest;
use App\Support\Attribution;
use App\Support\SpamGuard;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    /**
     * Store an eligibility form submission. Suspected bots get the same
     * success response, but nothing is saved.
     */
    public function store(StoreLeadRequest $request, SpamGuard $spamGuard, CaptureLead $captureLead): JsonResponse
    {
        if (! $spamGuard->isSpam($request)) {
            $captureLead->handle($request->validated(), Attribution::fromRequest($request), $request->ip());
        }

        return response()->json(['message' => 'Thank you, we have received your details.'], 201);
    }
}
