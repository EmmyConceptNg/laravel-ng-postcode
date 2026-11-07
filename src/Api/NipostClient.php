<?php

namespace EmmanuelKolawole\NgPostcode\Api;

use EmmanuelKolawole\NgPostcode\Exceptions\LookupLevelNotAllowed;
use EmmanuelKolawole\NgPostcode\Exceptions\MissingApiKey;
use EmmanuelKolawole\NgPostcode\Postcode;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use EmmanuelKolawole\NgPostcode\Exceptions\InsufficientCredits;
use EmmanuelKolawole\NgPostcode\Exceptions\InvalidApiKey;
use EmmanuelKolawole\NgPostcode\Exceptions\LevelNotGranted;
use EmmanuelKolawole\NgPostcode\Exceptions\NipostException;
use EmmanuelKolawole\NgPostcode\Exceptions\NipostUnavailable;
use EmmanuelKolawole\NgPostcode\Exceptions\RateLimitExceeded;
use EmmanuelKolawole\NgPostcode\State;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;

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
     * @throws NipostException when the API returns an error response or cannot be reached
     */
    public function lookup(Postcode $postcode, int $level = 1): LookupResult
    {
        if (! in_array($level, [1, 2, 3, 4, 5], true)) {
            throw new InvalidArgumentException("Invalid level {$level}. Level must be between 1 and 5.");
        }

        if ($level > $this->maxLevel) {
            throw LookupLevelNotAllowed::forLevel($level, $this->maxLevel);
        }

        $data = $this->send('/v1/lookup', [
            'code' => $postcode->canonical(),
            'level' => $level,
        ], $level);

        return LookupResult::fromArray($data);
    }

    /**
     * Sends a GET request and returns the response's "data" array.
     *
     * @param  int  $level  The lookup level requested, used to explain a level_not_granted error.
     *
     * @throws MissingApiKey when no API key is configured
     * @throws NipostException when NIPOST returns an error, an unexpected body, or cannot be reached
     */
    private function send(string $path, array $query = [], int $level = 1): array
    {
        $request = $this->request();

        try {
            $response = $request->get($path, $query);
        } catch (ConnectionException $e) {
            throw NipostUnavailable::unreachable($this->timeout, $e);
        }

        if ($response->failed()) {
            throw $this->exceptionFor($response, $level);
        }

        // A 200 without a data array means something other than the API answered.
        // Never let "no answer" look like "the postcode is invalid".
        $data = $response->json('data');

        if (! is_array($data)) {
            throw NipostException::unexpectedResponse($response->status());
        }

        return $data;
    }

    private function exceptionFor(Response $response, int $level): NipostException
    {
        $status = $response->status();
        $code = $response->json('error.code');

        if ($status === 401) {
            return InvalidApiKey::make($status, $code);
        }

        if ($status === 402) {
            return InsufficientCredits::make($status, $code);
        }

        if ($status === 403 && $code === 'level_not_granted') {
            return LevelNotGranted::forLevel($level, $status, $code);
        }

        if ($status === 429) {
            $retryAfter = $response->header('Retry-After');

            return RateLimitExceeded::make(is_numeric($retryAfter) ? (int) $retryAfter : null, $status, $code);
        }

        if ($status >= 500) {
            return NipostUnavailable::serverError($status, $code);
        }

        return new NipostException('The NIPOST API returned an unexpected error.', $status, $code);
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


    /** @return array<string, string> code => name, e.g. ['01' => 'Abaji', ...] */
    public function lgas(State $state): array
    {
        $data = $this->send('/v1/reference/lgas', [
            'state' => $state->value,
        ]);

        return collect($data['lgas'] ?? [])
            ->pluck('name', 'code')
            ->all();
    }

    /** @return list<string> district codes, e.g. ['A01', 'A02', ...] */
    public function districts(State $state, string $lga): array
    {
        $data = $this->send('/v1/reference/districts', [
            'state' => $state->value,
            'lga' => $lga,
        ]);

        return collect($data['districts'] ?? [])
            ->pluck('code')
            ->all();
    }

    /** @return list<string> area codes */
    public function areas(State $state, string $lga, string $district): array
    {
        $data = $this->send('/v1/reference/areas', [
            'state' => $state->value,
            'lga' => $lga,
            'district' => $district,
        ]);

        return collect($data['areas'] ?? [])
            ->pluck('code')
            ->all();
    }

    /** Convenience: the LGA name for a postcode, or null if NIPOST doesn't list it. */
    public function lgaName(Postcode $postcode): ?string
    {
        return $this->lgas($postcode->state)[$postcode->lga] ?? null;
    }
}
