<?php

namespace App\Providers;

use App\Support\AdminNavCounts;
use App\Support\MailConfigurator;
use App\Support\ResilientFilesystem;
use App\Support\Settings;
use App\Support\Site;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('files', fn (): ResilientFilesystem => new ResilientFilesystem);

        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureMail();
        $this->shareViewData();
    }

    /**
     * The public forms have no session or CSRF token; these per-IP limits are
     * their first line of abuse protection.
     */
    protected function configureRateLimiting(): void
    {
        foreach (['leads', 'contact', 'checkout', 'tracking'] as $name) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute(config('site.rate_limits.'.$name))->by($name.'|'.$request->ip()));
        }
    }

    /**
     * Apply the admin's SMTP settings now, and again before every queued job
     * so long-running workers pick up changes without a restart.
     */
    protected function configureMail(): void
    {
        MailConfigurator::apply();

        Event::listen(JobProcessing::class, function (): void {
            $this->app->make(Settings::class)->forgetLoaded();

            MailConfigurator::apply();
        });
    }

    protected function shareViewData(): void
    {
        ViewFacade::composer('layouts.site', fn (View $view) => $view->with([
            'contactEmail' => Site::email(),
            'contactWhatsapp' => Site::whatsapp(),
            'attributionDays' => Site::attributionDays(),
        ]));

        ViewFacade::composer('layouts.admin', fn (View $view) => $view->with('navCounts', AdminNavCounts::get()));
    }
}
