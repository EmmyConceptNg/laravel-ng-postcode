# Laravel NG Postcode

Parse, validate and format Nigeria's new NIPOST digital postcode in Laravel. The digital postcode launched on 1 October 2026. This package gives you a `Postcode` value object, a validation rule and an Eloquent cast, so your app can accept addresses in the new format.

## Installation

```bash
composer require emmyconceptng/laravel-ng-postcode
```

The service provider registers itself through Laravel's package auto-discovery, so you don't need to configure anything.

## The postcode format

A digital postcode has five segments, written from the largest area to the smallest:

```
EK - 01 - A03 - FK - 01
│    │    │     │    └── Building  (2 digits, 01–99)
│    │    │     └─────── Area      (2 letters)
│    │    └───────────── District  (3 letters or digits)
│    └────────────────── LGA       (2 digits, 01–99)
└─────────────────────── State     (2-letter state code, e.g. EK = Ekiti)
```

All 36 states and the FCT have their own code. NIPOST's documentation has the full specification: [Postcode format](https://docs.postcode.gov.ng/concepts/postcode-format).

## Usage

### Parsing and validating

```php
use EmmanuelKolawole\NgPostcode\Postcode;

$postcode = Postcode::parse('EK-01-A03-FK-01');   // Postcode instance

Postcode::parse('ek 01 a03 fk 01');               // also fine: spaces, hyphens and case are ignored

Postcode::parse('XX-01-A03-FK-01');
// throws InvalidArgumentException: "Unknown state code: XX"

Postcode::tryParse('not a postcode');             // null
```

Use `parse()` when bad input is a bug and should throw. Use `tryParse()` when you're checking user input and would rather get `null`.

### Formatting

```php
$postcode = Postcode::parse('ek01a03fk01');

$postcode->canonical();   // "EK-01-A03-FK-01"
$postcode->compact();     // "EK01A03FK01"
$postcode->spaced();      // "EK 01 A03 FK 01"
```

### Reading segments

Every segment is a read-only property. The state is a `State` enum.

```php
use EmmanuelKolawole\NgPostcode\State;

$postcode = Postcode::parse('EK-01-A03-FK-01');

$postcode->state;            // State::Ekiti
$postcode->state->value;     // "EK"
$postcode->state->label();   // "Ekiti"
$postcode->lga;              // "01"
$postcode->district;         // "A03"
$postcode->area;             // "FK"
$postcode->building;         // "01"

$postcode->state === State::Ekiti;   // true
```

`label()` gives you a human-readable name, for example `"Akwa Ibom"`, `"Cross River"` or `"FCT, Abuja"`.

### Validation rule

You can use the rule object:

```php
use EmmanuelKolawole\NgPostcode\Rules\NigerianPostcode;
use EmmanuelKolawole\NgPostcode\State;

$request->validate([
    // Any valid postcode
    'postcode'          => ['required', new NigerianPostcode],

    // Only postcodes in Lagos or Ogun
    'delivery_postcode' => ['required', new NigerianPostcode('LA', 'OG')],

    // The same, using the enum
    'office_postcode'   => ['required', new NigerianPostcode(State::Lagos, State::Ogun)],
]);
```

Or you can use the `ng_postcode` string rule:

```php
$request->validate([
    'postcode'          => 'required|ng_postcode',
    'delivery_postcode' => 'required|ng_postcode:LA,OG',
]);
```

When you restrict states, a postcode from another state fails validation. With the rule object, the message lists the allowed states ("The delivery postcode must be a postcode in LA, OG."). The string rule always shows the generic message ("The delivery postcode must be a valid Nigerian postcode.").

If you pass a state code that doesn't exist, you get a `ValueError`, so typos fail loudly instead of quietly rejecting every postcode. The rule object throws as soon as you create it. The string rule throws when validation runs.

### Eloquent cast

```php
use EmmanuelKolawole\NgPostcode\Casts\AsPostcode;

class Address extends Model
{
    protected function casts(): array
    {
        return [
            'postcode' => AsPostcode::class,
        ];
    }
}
```

```php
$address->postcode = 'ek-01-a03-fk-01';   // a string or a Postcode instance
$address->save();                         // saved as "EK01A03FK01"

$address->postcode;                       // Postcode instance
$address->postcode->canonical();          // "EK-01-A03-FK-01"
```

The cast saves the compact form (11 characters, no separators), so a `CHAR(11)` or `VARCHAR(11)` column is enough. Use `canonical()` or `spaced()` when you display a postcode.

## Good to know

- **It checks the format only.** A well-formed code isn't necessarily assigned to a real building. Lookups through the NIPOST API are planned.
- **Spaces, hyphens and letter case are ignored**, so `"ek 01 a03 fk 01"` is valid and becomes `EK-01-A03-FK-01`.
- **The cast is strict on write and forgiving on read.** Saving an invalid code throws an `InvalidArgumentException`. Reading an invalid value that's already in the database returns `null`, so old data won't crash your pages.
- **The old six-digit postcodes (like `100001`) are not accepted.** This package handles only the new digital format.
- LGA and building numbers run from `01` to `99`. `00` is rejected.
- **NIPOST's documentation calls the code 12 characters, but its own segments add up to 11** (2 + 2 + 3 + 2 + 2, e.g. `EK01A03FK01`). This package follows the segment format and expects 11.

## Requirements

- PHP 8.2 or higher
- Laravel 12 or 13

## Testing

```bash
composer install
vendor/bin/phpunit
```

## Contributing

Bug reports, ideas and pull requests are welcome. Please open an issue first if you're planning a big change, and add tests with your pull request.

## Credits

- [Emmanuel O. Kolawole](https://github.com/EmmyConceptNg)
- The postcode format is defined by NIPOST. See the [NIPOST postcode documentation](https://docs.postcode.gov.ng).

## License

MIT. See [LICENSE](LICENSE).
