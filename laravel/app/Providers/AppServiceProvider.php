<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One per request, so the list of stored map pictures is read from disk once.
        $this->app->singleton(\App\Services\MapImages::class);
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
    }
}
