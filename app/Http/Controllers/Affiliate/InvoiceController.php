<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Support\AffiliateStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private AffiliateStats $stats) {}

    /**
     * The month picker posts here; send the affiliate to that month's invoice.
     */
    public function index(Request $request): RedirectResponse
    {
        $month = (string) $request->query('month');

        if (! $this->stats->isCompletedMonth($month)) {
            return redirect()->route('affiliate.portal')
                ->withErrors(['month' => 'Choose a month that has already ended — invoices cover completed months only.']);
        }

        return redirect()->route('affiliate.invoices.show', $month);
    }

    /**
     * A print-optimised invoice for one completed month ("Save as PDF" in the browser).
     */
    public function show(Request $request, string $month): View
    {
        abort_unless($this->stats->isCompletedMonth($month), 404);

        $affiliate = $request->user()->affiliate;

        return view('affiliate.invoice', [
            'affiliate' => $affiliate->load('user'),
            'invoice' => $this->stats->invoice($affiliate, $month),
            'backUrl' => route('affiliate.portal'),
        ]);
    }
}
