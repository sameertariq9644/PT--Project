<?php

use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Affiliate\DashboardController;
use App\Http\Controllers\Affiliate\InvoiceController;
use App\Http\Controllers\Affiliate\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Session routes
|--------------------------------------------------------------------------
|
| Login, the admin panel and the affiliate portal. The public site lives in
| routes/public.php and never starts a session.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::redirect('/', '/admin/leads');

    Route::get('/leads', [LeadController::class, 'index'])->name('leads');
    Route::get('/leads/export', [LeadController::class, 'export'])->name('leads.export');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::post('/leads/{lead}/video', [LeadController::class, 'sendVideo'])->name('leads.video');
    Route::post('/leads/{lead}/email', [LeadController::class, 'sendEmail'])->name('leads.email');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->name('orders.reject');

    Route::get('/affiliates', [AffiliateController::class, 'index'])->name('affiliates');
    Route::get('/affiliates/stats', [AffiliateController::class, 'stats'])->name('affiliates.stats');
    Route::get('/affiliates/export', [AffiliateController::class, 'export'])->name('affiliates.export');
    Route::get('/affiliates/create', [AffiliateController::class, 'create'])->name('affiliates.create');
    Route::post('/affiliates', [AffiliateController::class, 'store'])->name('affiliates.store');
    Route::get('/affiliates/{affiliate}', [AffiliateController::class, 'show'])->name('affiliates.show');
    Route::get('/affiliates/{affiliate}/edit', [AffiliateController::class, 'edit'])->name('affiliates.edit');
    Route::put('/affiliates/{affiliate}', [AffiliateController::class, 'update'])->name('affiliates.update');
    Route::post('/affiliates/{affiliate}/payouts', [AffiliateController::class, 'markPaid'])->name('affiliates.payouts');
    Route::get('/affiliates/{affiliate}/invoices/{month}', [AffiliateController::class, 'invoice'])->name('affiliates.invoice');

    Route::get('/services', [PlanController::class, 'index'])->name('services');
    Route::get('/services/{plan}/edit', [PlanController::class, 'edit'])->name('services.edit');
    Route::put('/services/{plan}', [PlanController::class, 'update'])->name('services.update');

    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates');
    Route::post('/email-templates/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::get('/email-templates/create', [EmailTemplateController::class, 'create'])->name('email-templates.create');
    Route::post('/email-templates', [EmailTemplateController::class, 'store'])->name('email-templates.store');
    Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::delete('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingController::class, 'sendTestEmail'])->name('settings.test-email');
});

Route::prefix('affiliate')->name('affiliate.')->middleware(['auth', 'role:affiliate'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('portal');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
    Route::get('/invoices/{month}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
