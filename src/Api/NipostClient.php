<?php

namespace EmmanuelKolawole\NgPostcode\Api;

use EmmanuelKolawole\NgPostcode\Exceptions\LookupLevelNotAllowed;
use EmmanuelKolawole\NgPostcode\Exceptions\MissingApiKey;
use EmmanuelKolawole\NgPostcode\Postcode;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class NipostClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $maxLevel,
    ) {}

    /**
     * @throws InvalidArgumentException when the level is not 1–5
     * @throws LookupLevelNotAllowed when the level is above the configured max_level
     * @throws MissingApiKey when no API key is configured
     * @throws \Illuminate\Http\Client\RequestException when the API returns a 4xx/5xx response
     */
    public function lookup(Postcode $postcode, int $level = 1): array
    {
        if (! in_array($level, [1, 2, 3, 4, 5], true)) {
            throw new InvalidArgumentException("Invalid level {$level}. Level must be between 1 and 5.");
        }

        if ($level > $this->maxLevel) {
            throw LookupLevelNotAllowed::forLevel($level, $this->maxLevel);
        }

        $response = $this->request()->get('/v1/lookup', [
            'code' => $postcode->canonical(),
            'level' => $level,
        ])->throw();

        return $response->json('data', []);
    }

    private function request(): PendingRequest
    {
        if (empty($this->apiKey)) {
            throw MissingApiKey::make();
        }

        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout($this->timeout)
            ->acceptJson();
    }
}
