<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $cpRoute = config('statamic.cp.route', 'cp');

        Nav::extend(function ($nav) use ($cpRoute) {
            $nav->content('SSG Exporter')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>')
                ->url("/$cpRoute/ssg-exporter");
        });
    }
}
