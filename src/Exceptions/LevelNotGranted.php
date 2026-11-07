<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

class LevelNotGranted extends NipostException
{
    public static function forLevel(int $level, int $httpStatus = 403, ?string $errorCode = null): self
    {
        return new self(
            "Your NIPOST account has not been granted access to level {$level} lookups. "
            .'Request higher access under Settings → KYB & Access in the NIPOST dashboard, '
            .'or use a lower level until the request is approved.',
            $httpStatus,
            $errorCode,
        );
    }
}
