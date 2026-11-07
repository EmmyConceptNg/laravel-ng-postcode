<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

use RuntimeException;

class MissingApiKey extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'No NIPOST API key is configured, so API lookups cannot be made. '
            .'Add NIPOST_API_KEY=your-key to your .env file (or set "api_key" in config/ng-postcode.php). '
            .'If you cache your config, run "php artisan config:clear" afterwards.'
        );
    }
}
