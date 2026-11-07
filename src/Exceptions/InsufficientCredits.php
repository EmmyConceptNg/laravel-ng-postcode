<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

class InsufficientCredits extends NipostException
{
    public static function make(int $httpStatus = 402, ?string $errorCode = null): self
    {
        return new self(
            'Your NIPOST account does not have enough credits for this lookup. '
            .'Top up your balance in the NIPOST dashboard. '
            .'Level 1 lookups are free, so you can lower the requested level, '
            .'or set NIPOST_MAX_LEVEL=1 to make sure no credits are ever spent.',
            $httpStatus,
            $errorCode,
        );
    }
}
