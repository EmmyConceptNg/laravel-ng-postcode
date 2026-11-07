<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\NgPostcodeServiceProvider;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;

final class ConfigTest extends TestCase
{
    private const ENV_VARS = [
        'NIPOST_API_KEY',
        'NIPOST_BASE_URL',
        'NIPOST_TIMEOUT',
        'NIPOST_MAX_LEVEL',
        'NIPOST_CACHE_ENABLED',
        'NIPOST_CACHE_REFERENCE_TTL',
        'NIPOST_CACHE_LOOKUP_TTL',
    ];

    protected function getPackageProviders($app): array
    {
        return [NgPostcodeServiceProvider::class];
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_VARS as $name) {
            putenv($name);
        }

        parent::tearDown();
    }

    public function test_config_is_merged_under_the_ng_postcode_key(): void
    {
        $this->assertIsArray(config('ng-postcode'));
    }

    public function test_defaults_are_used_when_no_env_vars_are_set(): void
    {
        $this->assertNull(config('ng-postcode.api_key'));
        $this->assertSame('https://api.postcode.gov.ng', config('ng-postcode.base_url'));
        $this->assertSame(5, config('ng-postcode.timeout'));
        $this->assertSame(1, config('ng-postcode.max_level'));
        $this->assertTrue(config('ng-postcode.cache.enabled'));
        $this->assertSame(86400, config('ng-postcode.cache.reference_ttl'));
        $this->assertSame(3600, config('ng-postcode.cache.lookup_ttl'));
    }

    public function test_env_vars_override_the_defaults(): void
    {
        $this->bootWithEnv([
            'NIPOST_API_KEY' => 'test-key',
            'NIPOST_BASE_URL' => 'http://localhost:8080',
            'NIPOST_TIMEOUT' => '10',
            'NIPOST_MAX_LEVEL' => '3',
            'NIPOST_CACHE_ENABLED' => 'false',
            'NIPOST_CACHE_REFERENCE_TTL' => '604800',
            'NIPOST_CACHE_LOOKUP_TTL' => '60',
        ]);

        $this->assertSame('test-key', config('ng-postcode.api_key'));
        $this->assertSame('http://localhost:8080', config('ng-postcode.base_url'));
        $this->assertSame(10, config('ng-postcode.timeout'));
        $this->assertSame(3, config('ng-postcode.max_level'));
        $this->assertFalse(config('ng-postcode.cache.enabled'));
        $this->assertSame(604800, config('ng-postcode.cache.reference_ttl'));
        $this->assertSame(60, config('ng-postcode.cache.lookup_ttl'));
    }

    public function test_config_file_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(NgPostcodeServiceProvider::class, 'ng-postcode-config');

        $this->assertSame(
            [realpath(__DIR__ . '/../config/ng-postcode.php')],
            array_map('realpath', array_keys($paths)),
        );
        $this->assertSame([config_path('ng-postcode.php')], array_values($paths));
    }

    /**
     * Set env vars, then rebuild the app so the config file is read again.
     */
    private function bootWithEnv(array $vars): void
    {
        foreach ($vars as $name => $value) {
            putenv("{$name}={$value}");
        }

        $this->refreshApplication();
    }
}
