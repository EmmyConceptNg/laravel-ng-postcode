<?php

namespace EmmanuelKolawole\NgPostcode;

use EmmanuelKolawole\NgPostcode\Api\NipostClient;
use EmmanuelKolawole\NgPostcode\Rules\NigerianPostcode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

final class NgPostcodeServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ng-postcode.php', 'ng-postcode');

        $this->app->singleton(NipostClient::class, function ($app) {
            return new NipostClient(
                apiKey: config('ng-postcode.api_key'),
                baseUrl: config('ng-postcode.base_url'),
                timeout: config('ng-postcode.timeout'),
                maxLevel: config('ng-postcode.max_level'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ng-postcode.php' => config_path('ng-postcode.php'),
        ], 'ng-postcode-config');

        Validator::extend('ng_postcode', function (string $attribute, mixed $value, array $parameters): bool {
            $passes = true;

            (new NigerianPostcode(...$parameters))->validate(
                $attribute,
                $value,
                function () use (&$passes): void {
                    $passes = false;
                },
            );

            return $passes;
        }, 'The :attribute must be a valid Nigerian postcode.');
    }
}
