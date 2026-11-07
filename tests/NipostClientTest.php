<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\Api\AdministrativeAddress;
use EmmanuelKolawole\NgPostcode\Api\LookupResult;
use EmmanuelKolawole\NgPostcode\Api\NipostClient;
use EmmanuelKolawole\NgPostcode\Exceptions\InsufficientCredits;
use EmmanuelKolawole\NgPostcode\Exceptions\InvalidApiKey;
use EmmanuelKolawole\NgPostcode\Exceptions\LevelNotGranted;
use EmmanuelKolawole\NgPostcode\Exceptions\LookupLevelNotAllowed;
use EmmanuelKolawole\NgPostcode\Exceptions\MissingApiKey;
use EmmanuelKolawole\NgPostcode\Exceptions\NipostException;
use EmmanuelKolawole\NgPostcode\Exceptions\NipostUnavailable;
use EmmanuelKolawole\NgPostcode\Exceptions\RateLimitExceeded;
use EmmanuelKolawole\NgPostcode\NgPostcodeServiceProvider;
use EmmanuelKolawole\NgPostcode\Postcode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

final class NipostClientTest extends TestCase
{
    // NIPOST's documented sandbox code; test keys only resolve sandbox codes.
    private const SANDBOX_CODE = 'FC-01-A01-KP-27';

    protected function getPackageProviders($app): array
    {
        return [NgPostcodeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();   // any un-faked request fails the test
    }

    private function client(?string $apiKey = 'test-key', int $maxLevel = 1): NipostClient
    {
        return new NipostClient($apiKey, 'https://api.postcode.gov.ng', 5, $maxLevel);
    }

    /** A level 3 lookup response, shaped like the quickstart example in NIPOST's docs. */
    private function level3Data(): array
    {
        return [
            'postcode' => self::SANDBOX_CODE,
            'valid' => true,
            'status' => 'active',
            'verified' => true,
            'administrative_address' => [
                'state_name' => 'Federal Capital Territory',
                'lga_name' => 'Abuja Municipal',
                'locality_name' => 'Kubwa',
                'zone' => 'North Central',
            ],
            'recent_house_address' => [
                'recent' => '27 Example Street, Kubwa, Abuja',
            ],
            'building_use_status' => 'residential',
        ];
    }

    private function fakeLookup(?array $data = null): void
    {
        Http::fake([
            'api.postcode.gov.ng/v1/lookup*' => Http::response([
                'data' => $data ?? ['postcode' => self::SANDBOX_CODE, 'valid' => true, 'status' => 'active'],
            ]),
        ]);
    }

    private function fakeError(int $status, array|string $body): void
    {
        Http::fake(['api.postcode.gov.ng/v1/lookup*' => Http::response($body, $status)]);
    }

    private function lookup(int $level = 1): LookupResult
    {
        return $this->client(maxLevel: 3)->lookup(Postcode::parse(self::SANDBOX_CODE), $level);
    }

    /** Runs a lookup and returns the exception it throws, failing the test if none is thrown. */
    private function lookupException(): NipostException
    {
        try {
            $this->lookup();
        } catch (NipostException $e) {
            return $e;
        }

        $this->fail('Expected a NipostException to be thrown.');
    }

    public function test_lookup_sends_the_key_and_returns_a_lookup_result(): void
    {
        $this->fakeLookup();

        $result = $this->client()->lookup(Postcode::parse(self::SANDBOX_CODE));

        $this->assertInstanceOf(LookupResult::class, $result);
        $this->assertSame(self::SANDBOX_CODE, $result->postcode);
        $this->assertTrue($result->valid);

        Http::assertSent(
            fn(Request $request) =>
            $request->hasHeader('X-API-Key', 'test-key')
                && $request['code'] === self::SANDBOX_CODE
                && $request['level'] === 1
        );
    }

    public function test_missing_key_throws_and_sends_nothing(): void
    {
        Http::fake();

        try {
            $this->client(apiKey: null)->lookup(Postcode::parse(self::SANDBOX_CODE));
            $this->fail('Expected MissingApiKey to be thrown.');
        } catch (MissingApiKey) {
            Http::assertNothingSent();
        }
    }

    public function test_level_above_max_throws_and_sends_nothing(): void
    {
        Http::fake();

        try {
            $this->client(maxLevel: 1)->lookup(Postcode::parse(self::SANDBOX_CODE), level: 2);
            $this->fail('Expected LookupLevelNotAllowed to be thrown.');
        } catch (LookupLevelNotAllowed) {
            Http::assertNothingSent();
        }
    }

    public function test_level_within_max_is_allowed_and_sent(): void
    {
        $this->fakeLookup();

        $this->client(maxLevel: 3)->lookup(Postcode::parse(self::SANDBOX_CODE), level: 2);

        Http::assertSent(fn(Request $request) => $request['level'] === 2);
    }

    #[TestWith([0])]
    #[TestWith([6])]
    public function test_out_of_range_level_is_rejected(int $level): void
    {
        Http::fake();

        try {
            $this->client(maxLevel: 5)->lookup(Postcode::parse(self::SANDBOX_CODE), level: $level);
            $this->fail('Expected InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotInstanceOf(LookupLevelNotAllowed::class, $e);
            Http::assertNothingSent();
        }
    }

    public function test_not_found_is_a_result_not_an_exception(): void
    {
        $this->fakeLookup(['postcode' => self::SANDBOX_CODE, 'valid' => false, 'status' => 'not_found']);

        $result = $this->lookup();

        $this->assertFalse($result->valid);
        $this->assertSame('not_found', $result->status);
    }

    public function test_level_3_response_fills_the_detail_fields(): void
    {
        $this->fakeLookup($this->level3Data());

        $result = $this->lookup(level: 3);

        $this->assertTrue($result->verified);
        $this->assertInstanceOf(AdministrativeAddress::class, $result->administrativeAddress);
        $this->assertSame('Federal Capital Territory', $result->administrativeAddress->stateName);
        $this->assertSame('Abuja Municipal', $result->administrativeAddress->lgaName);
        $this->assertSame('Kubwa', $result->administrativeAddress->localityName);
        $this->assertSame('North Central', $result->administrativeAddress->zone);
        $this->assertSame('27 Example Street, Kubwa, Abuja', $result->recentHouseAddress);
        $this->assertSame('residential', $result->buildingUseStatus);
    }

    public function test_level_1_response_leaves_the_detail_fields_null(): void
    {
        $this->fakeLookup();

        $result = $this->lookup(level: 1);

        $this->assertNull($result->administrativeAddress);
        $this->assertNull($result->recentHouseAddress);
        $this->assertNull($result->buildingUseStatus);
    }

    public function test_raw_keeps_fields_the_result_does_not_map(): void
    {
        $this->fakeLookup([...$this->level3Data(), 'future_field' => 'kept']);

        $result = $this->lookup(level: 3);

        $this->assertSame('kept', $result->raw['future_field']);
    }

    #[TestWith([401, 'invalid_api_key', InvalidApiKey::class])]
    #[TestWith([402, 'insufficient_credits', InsufficientCredits::class])]
    #[TestWith([403, 'level_not_granted', LevelNotGranted::class])]
    #[TestWith([429, 'rate_limit_exceeded', RateLimitExceeded::class])]
    public function test_error_responses_throw_the_matching_exception(int $status, string $code, string $class): void
    {
        $this->fakeError($status, ['error' => ['code' => $code, 'message' => 'Something went wrong']]);

        $e = $this->lookupException();

        $this->assertInstanceOf($class, $e);
        $this->assertSame($status, $e->httpStatus);
        $this->assertSame($code, $e->errorCode);
    }

    public function test_rate_limit_reads_retry_after(): void
    {
        Http::fake(['api.postcode.gov.ng/v1/lookup*' => Http::response(
            ['error' => ['code' => 'rate_limit_exceeded', 'message' => 'Slow down']],
            429,
            ['Retry-After' => '30'],
        )]);

        $e = $this->lookupException();

        $this->assertInstanceOf(RateLimitExceeded::class, $e);
        $this->assertSame(30, $e->retryAfter);
    }

    public function test_server_error_throws_nipost_unavailable(): void
    {
        $this->fakeError(500, ['error' => ['code' => 'internal_error', 'message' => 'Oops']]);

        $e = $this->lookupException();

        $this->assertInstanceOf(NipostUnavailable::class, $e);
        $this->assertSame(500, $e->httpStatus);
    }

    public function test_timeout_throws_nipost_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $e = $this->lookupException();

        $this->assertInstanceOf(NipostUnavailable::class, $e);
        $this->assertSame(0, $e->httpStatus);
        $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
    }

    public function test_non_json_error_body_still_throws_the_matching_exception(): void
    {
        $this->fakeError(402, '<html><body>Payment Required</body></html>');

        $e = $this->lookupException();

        $this->assertInstanceOf(InsufficientCredits::class, $e);
        $this->assertSame(402, $e->httpStatus);
        $this->assertNull($e->errorCode);
    }

    public function test_403_with_another_code_throws_a_plain_nipost_exception(): void
    {
        $this->fakeError(403, ['error' => ['code' => 'scope_not_granted', 'message' => 'Scope not granted']]);

        $e = $this->lookupException();

        $this->assertSame(NipostException::class, $e::class);
        $this->assertSame(403, $e->httpStatus);
        $this->assertSame('scope_not_granted', $e->errorCode);
    }

    public function test_200_with_html_body_throws_instead_of_returning_an_invalid_result(): void
    {
        $this->fakeError(200, '<html><body>Welcome to nginx!</body></html>');

        $e = $this->lookupException();

        $this->assertSame(NipostException::class, $e::class);
        $this->assertSame(200, $e->httpStatus);
    }

    public function test_200_without_a_data_array_throws(): void
    {
        $this->fakeError(200, ['data' => 'not-an-array']);

        $this->assertSame(NipostException::class, $this->lookupException()::class);
    }

    public function test_non_array_administrative_address_is_treated_as_missing(): void
    {
        $this->fakeLookup([...$this->level3Data(), 'administrative_address' => 'Abuja']);

        $this->assertNull($this->lookup(level: 3)->administrativeAddress);
    }

    public function test_client_resolves_from_the_container(): void
    {
        $this->assertInstanceOf(NipostClient::class, app(NipostClient::class));
    }
}
