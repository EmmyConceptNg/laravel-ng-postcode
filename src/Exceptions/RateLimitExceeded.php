<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

use Throwable;

class RateLimitExceeded extends NipostException
{
    public function __construct(
        string $message,
        int $httpStatus = 429,
        ?string $errorCode = null,
        ?Throwable $previous = null,
        /** Seconds to wait before retrying, from NIPOST's Retry-After header, if sent. */
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $httpStatus, $errorCode, $previous);
    }

    public static function make(?int $retryAfter = null, int $httpStatus = 429, ?string $errorCode = null): self
    {
        $wait = $retryAfter !== null
            ? "Retry after {$retryAfter} second(s). "
            : 'Wait a moment before retrying. ';

        return new self(
            'Too many requests were sent to the NIPOST API. '.$wait
            .'For bulk work, queue lookups and throttle them, and cache results: a postcode rarely changes.',
            $httpStatus,
            $errorCode,
            retryAfter: $retryAfter,
        );
    }
}
