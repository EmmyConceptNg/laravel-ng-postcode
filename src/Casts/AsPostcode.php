<?php

namespace EmmanuelKolawole\NgPostcode\Casts;

use EmmanuelKolawole\NgPostcode\Postcode;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class AsPostcode implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Postcode
    {
        if ($value === null) {
            return null;
        }

        return Postcode::tryParse($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $postcode = $value instanceof Postcode ? $value : Postcode::parse((string) $value);

        return $postcode->compact();
    }
}
