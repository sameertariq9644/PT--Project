<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\Plan;
use App\Support\LeadForm;
use App\Support\Site;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public pages. They are stateless and cached at the edge, so nothing
 * here may depend on the visitor (no session, no auth, no per-request data).
 */
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'steps' => SiteContent::steps(),
            'plans' => Plan::cachedActive(),
            'faqs' => SiteContent::homeFaqs(),
            'leadFields' => LeadForm::fields(),
            'introVideoUrl' => Site::introVideoEmbedUrl(),
        ]);
    }

    public function pathway(): View
    {
        return view('pages.pathway', ['steps' => SiteContent::steps()]);
    }

    public function services(): View
    {
        return view('pages.services', [
            'plans' => Plan::cachedActive(),
            'comparison' => SiteContent::comparison(),
        ]);
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function faq(): View
    {
        $faqs = SiteContent::faqs();

        return view('pages.faq', [
            'faqs' => $faqs,
            'categories' => ['All', ...array_unique(array_column($faqs, 'category'))],
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'topics' => StoreContactMessageRequest::TOPICS,
            'email' => Site::email(),
            'emailUrl' => Site::emailUrl(),
            'whatsapp' => Site::whatsapp(),
            'whatsappUrl' => Site::whatsappUrl(),
        ]);
    }

    /**
     * The eligibility check lives on the home page; keep ?ref= and campaign
     * tags from affiliate links.
     */
    public function eligibility(Request $request): RedirectResponse
    {
        return redirect(route('home', $request->query()).'#eligibility');
    }
}
