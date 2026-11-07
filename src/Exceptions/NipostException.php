<?php

namespace EmmanuelKolawole\NgPostcode\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base class for every error raised while talking to the NIPOST API.
 *
 * Catch this to handle all API failures in one place:
 *
 *     try { ... } catch (NipostException $e) { $e->httpStatus; $e->errorCode; }
 */
class NipostException extends RuntimeException
{
    /**
     * @param  int  $httpStatus  HTTP status returned by NIPOST, or 0 when no response was received.
     * @param  string|null  $errorCode  The "code" from NIPOST's error envelope, if one was sent.
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    /** NIPOST answered, but not in the shape this package expects (e.g. an HTML page from a proxy). */
    public static function unexpectedResponse(int $httpStatus): self
    {
        return new self(
            "The NIPOST API returned an unexpected response (HTTP {$httpStatus}) without the expected \"data\" field. "
            .'This usually means a proxy, firewall or misconfigured NIPOST_BASE_URL is answering instead of the API. '
            .'Check that NIPOST_BASE_URL (or "base_url" in config/ng-postcode.php) points at the NIPOST API.',
            $httpStatus,
        );
    }
}
