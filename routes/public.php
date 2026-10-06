<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stateless public routes
|--------------------------------------------------------------------------
|
| Everything an anonymous visitor touches. These routes run in the slim
| "public" middleware group: no session, no encrypted cookies, no CSRF.
| Views rendered here must not contain per-visitor output (@csrf, old(),
| session(), auth()) because the responses are cached at the edge.
|
*/

Route::middleware('cache.public')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/pathway', [PageController::class, 'pathway'])->name('pathway');
    Route::get('/services', [PageController::class, 'services'])->name('services');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/checkout/{plan}', [CheckoutController::class, 'show'])->name('checkout.show');
});

// The eligibility check lives on the home page; keep ?ref= and campaign tags from affiliate links.
Route::get('/eligibility', [PageController::class, 'eligibility'])->name('eligibility');

Route::get('/orders/{order}/pay', [CheckoutController::class, 'pay'])->middleware('signed')->name('orders.pay');

Route::middleware('json')->group(function () {
    Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:leads')->name('leads.store');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
    Route::post('/checkout/{plan}', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
    Route::post('/orders/{order}/pay', [CheckoutController::class, 'proof'])->middleware(['signed', 'throttle:checkout'])->name('orders.proof');
    Route::post('/t/visit', [TrackingController::class, 'store'])->middleware('throttle:tracking')->name('tracking.visit');
});
