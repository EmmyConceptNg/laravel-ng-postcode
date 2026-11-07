<?php

namespace EmmanuelKolawole\NgPostcode\Api;

use Closure;
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
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;

final class NipostClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $maxLevel,
        private readonly ?Repository $cache = null,
        private readonly int $referenceTtl = 86400,
        private readonly int $lookupTtl = 3600,
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

        // The level checks above stay outside the cache, so lowering max_level
        // can't be bypassed by a result cached at a higher level. The raw array
        // is cached rather than the LookupResult object.
        $data = $this->remember(
            "lookup:{$postcode->canonical()}:{$level}",
            $this->lookupTtl,
            fn () => $this->send('/v1/lookup', [
                'code' => $postcode->canonical(),
                'level' => $level,
            ], $level),
        );

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

    /**
     * Returns the cached value for the key, or runs the callback and caches it.
     * If the callback throws, nothing is stored, so errors are never cached.
     *
     * Keys include a short fingerprint of the API key, so switching from a test
     * key to a live key never serves sandbox answers from the cache. It is a hash
     * because cache key names can be inspected and must not contain the real key.
     *
     * @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    private function remember(string $key, int $ttl, Closure $callback): mixed
    {
        if ($this->cache === null) {
            return $callback();
        }

        return $this->cache->remember("ng-postcode:{$this->keyFingerprint()}:{$key}", $ttl, $callback);
    }

    private function keyFingerprint(): string
    {
        return substr(hash('sha256', (string) $this->apiKey), 0, 12);
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
        return $this->remember("lgas:{$state->value}", $this->referenceTtl, function () use ($state) {
            $data = $this->send('/v1/reference/lgas', ['state' => $state->value]);

            return collect($data['lgas'] ?? [])->pluck('name', 'code')->all();
        });
    }

    /** @return list<string> district codes, e.g. ['A01', 'A02', ...] */
    public function districts(State $state, string $lga): array
    {
        return $this->remember("districts:{$state->value}:{$lga}", $this->referenceTtl, function () use ($state, $lga) {
            $data = $this->send('/v1/reference/districts', [
                'state' => $state->value,
                'lga' => $lga,
            ]);

            return collect($data['districts'] ?? [])
                ->pluck('code')
                ->all();
        });
    }

    /** @return list<string> area codes */
    public function areas(State $state, string $lga, string $district): array
    {
        return $this->remember("areas:{$state->value}:{$lga}:{$district}", $this->referenceTtl, function () use ($state, $lga, $district) {
            $data = $this->send('/v1/reference/areas', [
                'state' => $state->value,
                'lga' => $lga,
                'district' => $district,
            ]);

            return collect($data['areas'] ?? [])
                ->pluck('code')
                ->all();
        });
    }

    /** Convenience: the LGA name for a postcode, or null if NIPOST doesn't list it. */
    public function lgaName(Postcode $postcode): ?string
    {
        return $this->lgas($postcode->state)[$postcode->lga] ?? null;
    }
}
