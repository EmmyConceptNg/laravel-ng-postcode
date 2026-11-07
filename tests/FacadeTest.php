<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\Api\NipostClient;
use EmmanuelKolawole\NgPostcode\Facades\NgPostcode;
use EmmanuelKolawole\NgPostcode\NgPostcodeServiceProvider;
use EmmanuelKolawole\NgPostcode\State;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

final class FacadeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [NgPostcodeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        // Must be set before the singleton is first resolved, since the provider reads config then.
        config(['ng-postcode.api_key' => 'test-key']);
    }

    public function test_lgas_returns_the_faked_lga_map(): void
    {
        Http::fake([
            'api.postcode.gov.ng/v1/reference/lgas*' => Http::response([
                'data' => ['lgas' => [
                    ['code' => '01', 'name' => 'Abaji'],
                    ['code' => '02', 'name' => 'Abuja Municipal'],
                ]],
            ]),
        ]);

        $this->assertSame(
            ['01' => 'Abaji', '02' => 'Abuja Municipal'],
            NgPostcode::lgas(State::FCT),
        );
    }

    public function test_facade_uses_the_container_singleton(): void
    {
        $this->assertSame(app(NipostClient::class), NgPostcode::getFacadeRoot());
    }
}
