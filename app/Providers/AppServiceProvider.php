<?php

namespace App\Providers;

use App\Support\Site;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Site::class);
    }

    public function boot(): void
    {
        /*
         * Navigation and site identity come from the database, never from
         * Blade. Sharing one lazily-resolved accessor keeps every template in
         * sync without each controller having to remember to pass it, and
         * costs nothing on requests that do not render a front-end view.
         */
        View::share('site', $this->app->make(Site::class));
    }
}
