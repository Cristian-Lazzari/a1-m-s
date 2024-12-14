<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Condivide la variabile $notifications con tutte le viste
        // view()->composer('*', function ($view) {
        //     $notifications = Notification::orderByDesc('created_at')->get();
        //     $view->with('notifications', $notifications);
        // });
    }
}
