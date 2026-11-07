<?php

namespace EmmanuelKolawole\NgPostcode\Tests;

use EmmanuelKolawole\NgPostcode\Casts\AsPostcode;
use EmmanuelKolawole\NgPostcode\Postcode;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

final class AsPostcodeCastTest extends TestCase
{
    private function address(): Model
    {
        return new class extends Model {
            protected function casts(): array
            {
                return ['postcode' => AsPostcode::class];
            }
        };
    }

    public function test_setting_a_string_stores_the_compact_form(): void
    {
        $address = $this->address();
        $address->postcode = 'ek 01 a03 fk 01';

        $this->assertSame('EK01A03FK01', $address->getAttributes()['postcode']);
    }


    public function test_reading_the_attribute_returns_a_postcode(): void
    {
        $address = $this->address();
        $address->setRawAttributes(['postcode' => 'EK01A03FK01']);

        $postcode = $address->postcode;

        $this->assertInstanceOf(Postcode::class, $postcode);
        $this->assertSame('EK-01-A03-FK-01', $postcode->canonical());
    }

    public function test_setting_a_postcode_object_stores_the_compact_form(): void
    {
        $address = $this->address();
        $address->postcode = Postcode::parse('EK-01-A03-FK-01');

        $this->assertSame('EK01A03FK01', $address->getAttributes()['postcode']);
    }


    public function test_null_stays_null(): void
    {
        $address = $this->address();
        $address->postcode = null;

        $this->assertNull($address->getAttributes()['postcode']);
        $this->assertNull($address->postcode);
    }


    public function test_setting_an_invalid_string_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $address = $this->address();
        $address->postcode = 'EK-00-A03-FK-01'; // LGA 00 is invalid
    }

    public function test_a_legacy_invalid_value_reads_as_null(): void
    {
        $address = $this->address();
        $address->setRawAttributes(['postcode' => '930283']);

        $this->assertNull($address->postcode);
    }
}
