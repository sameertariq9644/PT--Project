<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Support\Site;
use App\Support\SpamGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request, SpamGuard $spamGuard): JsonResponse
    {
        if (! $spamGuard->isSpam($request)) {
            $message = ContactMessage::query()->create($request->validated());

            $recipients = Site::recipients('notifications.lead_recipients');

            if ($recipients !== []) {
                Mail::to($recipients)->queue(new ContactMessageMail($message));
            }
        }

        return response()->json(['message' => 'Thank you — your message has been sent.'], 201);
    }
}
