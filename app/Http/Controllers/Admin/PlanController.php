<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.services', [
            'plans' => Plan::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function edit(Plan $plan): View
    {
        return view('admin.service-form', ['plan' => $plan]);
    }

    /**
     * Saving a plan flushes the cached list the public pages read (see Plan::booted).
     */
    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->planAttributes());

        return redirect()->route('admin.services')->with('status', '“'.$plan->name.'” saved. The public site now shows the new details.');
    }
}
