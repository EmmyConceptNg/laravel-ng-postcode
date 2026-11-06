<?php

namespace EmmanuelKolawole\NgPostcode;

use EmmanuelKolawole\NgPostcode\Rules\NigerianPostcode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

final class NgPostcodeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
        },'The :attribute must be a valid Nigerian postcode.');
    }
}
