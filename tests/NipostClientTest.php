<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\Api\NipostClient;
use EmmanuelKolawole\NgPostcode\Exceptions\LookupLevelNotAllowed;
use EmmanuelKolawole\NgPostcode\Exceptions\MissingApiKey;
use EmmanuelKolawole\NgPostcode\NgPostcodeServiceProvider;
use EmmanuelKolawole\NgPostcode\Postcode;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
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

    private function fakeLookup(): void
    {
        Http::fake([
            'api.postcode.gov.ng/v1/lookup*' => Http::response([
                'data' => ['postcode' => self::SANDBOX_CODE, 'valid' => true],
            ]),
        ]);
    }

    public function test_lookup_sends_the_key_and_returns_data(): void
    {
        $this->fakeLookup();

        $data = $this->client()->lookup(Postcode::parse(self::SANDBOX_CODE));

        $this->assertTrue($data['valid']);

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

    public function test_api_error_response_throws(): void
    {
        Http::fake([
            'api.postcode.gov.ng/v1/lookup*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->expectException(RequestException::class);

        $this->client()->lookup(Postcode::parse(self::SANDBOX_CODE));
    }

    public function test_client_resolves_from_the_container(): void
    {
        $this->assertInstanceOf(NipostClient::class, app(NipostClient::class));
    }
}
