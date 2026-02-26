<?php

namespace App\Providers;

use App\Contracts\DocumentParserInterface;
use App\Services\OCR\DocumentManager;
use App\Services\OCR\ImageParser;
use App\Services\OCR\PdfParser;
use App\Services\OCR\WordParser;
use Illuminate\Support\ServiceProvider;

class DocumentParserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register individual parsers as singletons
        $this->app->singleton(ImageParser::class, function ($app) {
            return new ImageParser();
        });

        $this->app->singleton(WordParser::class, function ($app) {
            return new WordParser();
        });

        $this->app->singleton(PdfParser::class, function ($app) {
            return new PdfParser();
        });

        // Register DocumentManager with all parsers
        $this->app->singleton(DocumentManager::class, function ($app) {
            $manager = new DocumentManager();

            // Register all parsers
            $manager->registerParser($app->make(ImageParser::class));
            $manager->registerParser($app->make(WordParser::class));
            $manager->registerParser($app->make(PdfParser::class));

            return $manager;
        });

        // Bind interface to manager for type-hinting flexibility
        $this->app->bind(DocumentParserInterface::class, function ($app) {
            // Default to returning the manager (or a specific parser if needed)
            return $app->make(DocumentManager::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
