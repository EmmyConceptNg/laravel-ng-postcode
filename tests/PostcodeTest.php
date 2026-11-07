<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\Postcode;
use EmmanuelKolawole\NgPostcode\State;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PostcodeTest extends TestCase
{
    public static function validInputs(): array
    {
        return [
            'canonical'        => ['EK-01-A03-FK-01'],
            'spaced'           => ['EK 01 A03 FK 01'],
            'compact'          => ['EK01A03FK01'],
            'lowercase'        => ['ek-01-a03-fk-01'],
            'mixed separators' => ['ek 01-A03 fk-01'],
            'tab and padding'  => ["  EK\t01 A03 FK 01  "],
            'NIPOST sample'    => ['FC 02 A09 DB 09'],
            'all-digit district' => ['LA-15-123-AB-10'],
            'max LGA/building' => ['ZA-99-ZZZ-ZZ-99'],
        ];
    }

    #[DataProvider('validInputs')]
    public function test_it_accepts_valid_postcodes(string $input): void
    {
        $this->assertNotNull(Postcode::tryParse($input));
    }

    public static function invalidInputs(): array
    {
        return [
            'old six-digit code'      => ['930283'],
            'empty string'            => [''],
            'unknown state'           => ['XX-01-A03-FK-01'],
            'digit in state'          => ['1K-01-A03-FK-01'],
            'LGA 00'                  => ['EK-00-A03-FK-01'],
            'building 00'             => ['EK-01-A03-FK-00'],
            'letter O in LGA'         => ['EK-O1-A03-FK-01'],
            'letter O in building'    => ['EK-01-A03-FK-O1'],
            'symbol in district'      => ['EK-01-A0#-FK-01'],
            'digit in area'           => ['EK-01-A03-F1-01'],
            'too short'               => ['EK-01-A03-FK-1'],
            'too long'                => ['EK-01-A03-FK-011'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_it_rejects_invalid_postcodes(string $input): void
    {
        $this->assertNull(Postcode::tryParse($input));
    }

    public function test_canonical_format(): void
    {
        $this->assertSame('EK-01-A03-FK-01', Postcode::parse('ek 01 a03 fk 01')->canonical());
    }

    public function test_parse_exposes_each_segment(): void
    {
        $postcode = Postcode::parse('FC 02 A09 DB 09');

        $this->assertSame(State::FCT, $postcode->state);
        $this->assertSame('02', $postcode->lga);
        $this->assertSame('A09', $postcode->district);
        $this->assertSame('DB', $postcode->area);
        $this->assertSame('09', $postcode->building);
    }

    public function test_parse_throws_naming_the_bad_segment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LGA');

        Postcode::parse('EK-00-A03-FK-01');
    }

    public function test_parse_throws_for_unknown_state(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown state code: XX');

        Postcode::parse('XX-01-A03-FK-01');
    }

    public function test_state_enum_has_all_37_codes(): void
    {
        $this->assertCount(37, State::cases());
    }

    public static function stateLabels(): array
    {
        return [
            'plain name'  => [State::Lagos, 'Lagos'],
            'Akwa Ibom'   => [State::AkwaIbom, 'Akwa Ibom'],
            'Cross River' => [State::CrossRiver, 'Cross River'],
            'FCT'         => [State::FCT, 'Federal Capital Territory'],
        ];
    }

    #[DataProvider('stateLabels')]
    public function test_state_label(State $state, string $expected): void
    {
        $this->assertSame($expected, $state->label());
    }

    public function test_output_formats(): void
    {
        $postcode = Postcode::parse('ek01a03fk01');

        $this->assertSame('EK-01-A03-FK-01', $postcode->canonical());
        $this->assertSame('EK01A03FK01', $postcode->compact());
        $this->assertSame('EK 01 A03 FK 01', $postcode->spaced());
    }
}
