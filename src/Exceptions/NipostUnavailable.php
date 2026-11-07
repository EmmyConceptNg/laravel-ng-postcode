<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

use Throwable;

class NipostUnavailable extends NipostException
{
    /**
     * No response was received: a timeout, DNS failure, refused connection or no network.
     * Laravel reports all of these as a ConnectionException, so they share one message.
     */
    public static function unreachable(int $timeoutSeconds, ?Throwable $previous = null): self
    {
        return new self(
            'The NIPOST API could not be reached (timeout or network error). '
            ."This is usually temporary, so try again shortly. If it keeps happening, check your server's internet access and DNS, "
            .'that NIPOST_BASE_URL (or "base_url" in config/ng-postcode.php) is correct, '
            ."and consider raising NIPOST_TIMEOUT (currently {$timeoutSeconds} second(s)).",
            0,
            null,
            $previous,
        );
    }

    public static function serverError(int $httpStatus, ?string $errorCode = null, ?Throwable $previous = null): self
    {
        return new self(
            "The NIPOST API returned a server error (HTTP {$httpStatus}). "
            .'The problem is on NIPOST\'s side, not your request; try again later.',
            $httpStatus,
            $errorCode,
            $previous,
        );
    }
}
