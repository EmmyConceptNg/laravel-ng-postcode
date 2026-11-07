<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

class InvalidApiKey extends NipostException
{
    public static function make(int $httpStatus = 401, ?string $errorCode = null): self
    {
        return new self(
            'NIPOST rejected the configured API key. '
            .'Check that NIPOST_API_KEY in your .env file matches an active key in the NIPOST dashboard, '
            .'with no extra spaces or quotes, and that the key has not been revoked or rotated. '
            .'If you cache your config, run "php artisan config:clear" after changing it.',
            $httpStatus,
            $errorCode,
        );
    }
}
