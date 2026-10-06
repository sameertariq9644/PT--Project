<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Affiliate\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('affiliate.profile', [
            'user' => $request->user(),
            'affiliate' => $request->user()->affiliate,
        ]);
    }

    /**
     * Update the affiliate's own payout details and, optionally, password.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->affiliate->update(['payout_details' => $request->validated('payout_details')]);

        if (filled($request->validated('password'))) {
            $user->password = $request->validated('password');
            $user->save();
        }

        return redirect()->route('affiliate.profile')->with('status', 'Your details have been saved.');
    }
}
