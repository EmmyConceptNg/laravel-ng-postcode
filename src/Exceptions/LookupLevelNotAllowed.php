<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

use InvalidArgumentException;

class LookupLevelNotAllowed extends InvalidArgumentException
{
    public static function forLevel(int $requested, int $max): self
    {
        return new self(
            "Lookup level {$requested} was requested, but the maximum allowed level is {$max}. "
            ."To allow it, set NIPOST_MAX_LEVEL={$requested} in your .env file (or \"max_level\" in config/ng-postcode.php). "
            .'Note that levels above 1 spend NIPOST credits on every lookup.'
        );
    }
}
