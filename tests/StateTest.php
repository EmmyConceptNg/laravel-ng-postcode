<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\State;
use PHPUnit\Framework\TestCase;

final class StateTest extends TestCase
{
    /**
     * The official list from NIPOST's GET /v1/reference/states endpoint,
     * retrieved 2026-11-07. Update this if NIPOST changes the codes.
     */
    private const NIPOST_STATES = [
        'AB' => 'Abia',
        'AD' => 'Adamawa',
        'AK' => 'Akwa Ibom',
        'AN' => 'Anambra',
        'BA' => 'Bauchi',
        'BE' => 'Benue',
        'BR' => 'Borno',
        'BY' => 'Bayelsa',
        'CR' => 'Cross River',
        'DE' => 'Delta',
        'EB' => 'Ebonyi',
        'ED' => 'Edo',
        'EK' => 'Ekiti',
        'EN' => 'Enugu',
        'FC' => 'Federal Capital Territory',
        'GM' => 'Gombe',
        'IM' => 'Imo',
        'JI' => 'Jigawa',
        'KD' => 'Kaduna',
        'KE' => 'Kebbi',
        'KG' => 'Kogi',
        'KN' => 'Kano',
        'KT' => 'Katsina',
        'KW' => 'Kwara',
        'LA' => 'Lagos',
        'NA' => 'Nasarawa',
        'NI' => 'Niger',
        'OG' => 'Ogun',
        'ON' => 'Ondo',
        'OS' => 'Osun',
        'OY' => 'Oyo',
        'PL' => 'Plateau',
        'RI' => 'Rivers',
        'SK' => 'Sokoto',
        'TA' => 'Taraba',
        'YB' => 'Yobe',
        'ZA' => 'Zamfara',
    ];

    public function test_enum_codes_match_the_official_nipost_list(): void
    {
        $official = array_keys(self::NIPOST_STATES);
        $enum = array_map(fn (State $s) => $s->value, State::cases());

        sort($official);
        sort($enum);

        $this->assertSame($official, $enum);
    }

    public function test_labels_match_the_official_nipost_names(): void
    {
        foreach (self::NIPOST_STATES as $code => $name) {
            $this->assertSame($name, State::from($code)->label(), "Label for $code");
        }
    }
}
