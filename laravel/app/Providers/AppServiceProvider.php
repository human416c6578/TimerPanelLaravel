<?php

namespace App\Providers;

use App\Services\ServerStatus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Run times and played times were formatted by four separate copies of
        // the same arithmetic spread across the views; these are the only two.
        Blade::directive('runtime', fn ($expression) => "<?php echo \App\Support\TimeFormat::runtime({$expression}); ?>");
        Blade::directive('delta', fn ($expression) => "<?php echo \App\Support\TimeFormat::delta({$expression}); ?>");
        Blade::directive('played', fn ($expression) => "<?php echo \App\Support\TimeFormat::played({$expression}); ?>");

        // The sidebar shows live server status on every page. This is a cached
        // UDP query against the game servers, not a database read.
        View::composer('components.layouts.app.sidebar', function ($view) {
            $view->with('liveServers', app(ServerStatus::class)->all());
        });
    }
}
