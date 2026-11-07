<?php

namespace EmmanuelKolawole\NgPostcode\Facades;

use EmmanuelKolawole\NgPostcode\Api\LookupResult;
use EmmanuelKolawole\NgPostcode\Api\NipostClient;
use EmmanuelKolawole\NgPostcode\Postcode;
use EmmanuelKolawole\NgPostcode\State;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LookupResult lookup(Postcode $postcode, int $level = 1)
 * @method static array<string, string> lgas(State $state)
 * @method static list<string> districts(State $state, string $lga)
 * @method static list<string> areas(State $state, string $lga, string $district)
 * @method static ?string lgaName(Postcode $postcode)
 *
 * @see NipostClient
 */
final class NgPostcode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NipostClient::class;
    }
}
