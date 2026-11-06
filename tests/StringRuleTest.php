<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\NgPostcodeServiceProvider;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;

final class StringRuleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [NgPostcodeServiceProvider::class];
    }

    public function test_string_rule_accepts_a_valid_postcode(): void
    {
        $validator = Validator::make(['postcode' => 'EK-01-A03-FK-01'], ['postcode' => 'ng_postcode']);

        $this->assertTrue($validator->passes());
    }

    public function test_string_rule_rejects_an_invalid_postcode(): void
    {
        $validator = Validator::make(['postcode' => 'EK-00-A03-FK-01'], ['postcode' => 'ng_postcode']);

        $this->assertFalse($validator->passes());
    }

    public function test_string_rule_rejects_a_non_string_without_crashing(): void
    {
        $validator = Validator::make(['postcode' => ['EK-01-A03-FK-01']], ['postcode' => 'ng_postcode']);

        $this->assertFalse($validator->passes());
    }

    public function test_state_parameters_accept_an_allowed_state(): void
    {
        $validator = Validator::make(['postcode' => 'LA-01-A03-FK-01'], ['postcode' => 'ng_postcode:LA,OG']);

        $this->assertTrue($validator->passes());
    }

    public function test_state_parameters_reject_another_state(): void
    {
        $validator = Validator::make(['postcode' => 'KN-01-A03-FK-01'], ['postcode' => 'ng_postcode:LA,OG']);

        $this->assertFalse($validator->passes());
    }

    public function test_error_message_uses_the_field_name(): void
    {
        $validator = Validator::make(['delivery_postcode' => 'EK-00-A03-FK-01'], ['delivery_postcode' => 'ng_postcode']);

        $this->assertSame(
            'The delivery postcode must be a valid Nigerian postcode.',
            $validator->errors()->first('delivery_postcode'),
        );
    }
}
