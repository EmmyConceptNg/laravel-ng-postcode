<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\Rules\NigerianPostcode;
use EmmanuelKolawole\NgPostcode\State;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\TestCase;

final class NigerianPostcodeRuleTest extends TestCase
{
    private function passes(mixed $value, NigerianPostcode $rule): bool
    {
        return $this->validator($value, $rule)->passes();
    }

    private function validator(mixed $value, NigerianPostcode $rule): \Illuminate\Validation\Validator
    {
        $factory = new Factory(new Translator(new ArrayLoader(), 'en'));

        return $factory->make(['postcode' => $value], ['postcode' => [$rule]]);
    }

    public function test_it_accepts_a_valid_postcode(): void
    {
        $this->assertTrue($this->passes('EK-01-A03-FK-01', new NigerianPostcode));
    }

    public function test_it_rejects_an_invalid_postcode(): void
    {
        $this->assertFalse($this->passes('EK-00-A03-FK-01', new NigerianPostcode));
    }

    public function test_it_rejects_an_array_without_crashing(): void
    {
        $this->assertFalse($this->passes(['EK-01-A03-FK-01'], new NigerianPostcode));
    }

    public function test_it_rejects_an_integer_without_crashing(): void
    {
        $this->assertFalse($this->passes(930283, new NigerianPostcode));
    }

    public function test_state_restriction_accepts_an_allowed_state(): void
    {
        $rule = new NigerianPostcode('LA', 'OG');

        $this->assertTrue($this->passes('LA-01-A03-FK-01', $rule));
        $this->assertTrue($this->passes('og 01 a03 fk 01', $rule));
    }

    public function test_state_restriction_rejects_another_state(): void
    {
        $this->assertFalse($this->passes('KN-01-A03-FK-01', new NigerianPostcode('LA', 'OG')));
    }

    public function test_lowercase_state_code_still_works(): void
    {
        $this->assertTrue($this->passes('LA-01-A03-FK-01', new NigerianPostcode('la')));
    }

    public function test_state_enum_can_be_passed_instead_of_a_string(): void
    {
        $this->assertTrue($this->passes('LA-01-A03-FK-01', new NigerianPostcode(State::Lagos)));
        $this->assertFalse($this->passes('KN-01-A03-FK-01', new NigerianPostcode(State::Lagos)));
    }

    public function test_unknown_state_code_throws_when_constructing_the_rule(): void
    {
        $this->expectException(\ValueError::class);

        new NigerianPostcode('LG');
    }

    public function test_invalid_postcode_message(): void
    {
        $errors = $this->validator('EK-00-A03-FK-01', new NigerianPostcode)->errors();

        $this->assertSame('The postcode must be a valid Nigerian postcode.', $errors->first('postcode'));
    }

    public function test_wrong_state_message_lists_allowed_states(): void
    {
        $errors = $this->validator('KN-01-A03-FK-01', new NigerianPostcode('LA', 'OG'))->errors();

        $this->assertSame('The postcode must be a postcode in LA, OG.', $errors->first('postcode'));
    }
}
