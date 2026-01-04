<?php
// filepath: app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use App\Models\User;

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
        // Single Scramble configuration with all settings
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            })
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            });

        // Register API version
        Scramble::registerApi('v1', ['info' => ['version' => '1.0']])
            ->expose(
                ui: '/docs/v1/api',
                document: '/docs/v1/openapi.json',
            );

        // Gate for API docs access
        Gate::define('viewApiDocs', function () {
            return true;
        });
    }
}