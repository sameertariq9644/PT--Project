<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Actions\SubmitPaymentProof;
use App\Enums\OrderStatus;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\SubmitPaymentProofRequest;
use App\Models\Order;
use App\Models\Plan;
use App\Support\Attribution;
use App\Support\Site;
use App\Support\SiteTime;
use App\Support\SpamGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Step 1: the buyer's details and the order summary. Cached at the edge
     * like the other public pages, so it only reads the cached plans.
     */
    public function show(string $plan): View
    {
        return view('checkout.show', [
            'plan' => Plan::findActiveBySlug($plan) ?? abort(404),
            'whatsapp' => Site::whatsapp(),
            'whatsappUrl' => Site::whatsappUrl(),
        ]);
    }

    /**
     * Create the order and hand back the signed link to step 2.
     */
    public function store(PlaceOrderRequest $request, string $plan, SpamGuard $spamGuard, PlaceOrder $placeOrder): JsonResponse
    {
        $plan = Plan::findActiveBySlug($plan) ?? abort(404);

        if ($plan->price === null) {
            return response()->json(['message' => 'This plan cannot be bought online yet. Please contact us and we will help you directly.'], 422);
        }

        if ($spamGuard->isSpam($request)) {
            return response()->json(['message' => 'We could not submit the form. Please reload the page and try again.'], 422);
        }

        $order = $placeOrder->handle($plan, $request->validated(), Attribution::fromRequest($request));

        return response()->json(['redirect' => URL::signedRoute('orders.pay', ['order' => $order])], 201);
    }

    /**
     * Step 2: bank details and the receipt upload. Reached only through the
     * signed link, and never cached.
     */
    public function pay(Order $order): Response
    {
        return response()
            ->view('checkout.pay', [
                'order' => $order,
                'bank' => Site::bankDetails(),
                'proofUrl' => URL::signedRoute('orders.proof', ['order' => $order]),
                'today' => SiteTime::today()->toDateString(),
                'whatsapp' => Site::whatsapp(),
                'whatsappUrl' => Site::whatsappUrl(),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function proof(SubmitPaymentProofRequest $request, Order $order, SubmitPaymentProof $submitPaymentProof): JsonResponse
    {
        $submitted = $order->status === OrderStatus::AwaitingPayment
            && $submitPaymentProof->handle($order, $request->safe()->only(['transaction_id', 'transferred_on']), $request->file('receipt'));

        if (! $submitted) {
            return response()->json(['message' => 'A receipt has already been submitted for this order. Please contact us if you need to change it.'], 409);
        }

        return response()->json(['message' => 'Thank you, we have received your receipt.'], 201);
    }
}
