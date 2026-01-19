<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
         Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Borrowing System Policy
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\Borrowing::class,
            \App\Policies\BorrowingPolicy::class
        );
    }
}
