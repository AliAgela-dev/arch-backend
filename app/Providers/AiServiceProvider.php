<?php

namespace App\Providers;

use App\Contracts\AiClientInterface;
use App\Contracts\EmbeddingClientInterface;
use App\Services\AI\GeminiClient;
use App\Services\Embedding\GeminiEmbeddingClient;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register AI service bindings.
     *
     * Singleton because both clients read config in constructor
     * and maintain rate limiter state.
     */
    public function register(): void
    {
        $this->app->singleton(AiClientInterface::class, GeminiClient::class);
        $this->app->singleton(EmbeddingClientInterface::class, GeminiEmbeddingClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
