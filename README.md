# Laravel NG Postcode

Parse, validate and format Nigeria's new NIPOST digital postcode in Laravel. The digital postcode launched on 1 October 2026. This package gives you a `Postcode` value object, a validation rule, an Eloquent cast and a client for the NIPOST API, so your app can accept and check addresses in the new format.

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

`label()` gives you a human-readable name, for example `"Akwa Ibom"`, `"Cross River"` or `"Federal Capital Territory"`.

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

## NIPOST API

Parsing, validating and formatting all work offline. To check whether a postcode is actually assigned, or to find out where it is, the package can call the NIPOST postcode API.

### Setup

Get an API key from the NIPOST dashboard (see [NIPOST's documentation](https://docs.postcode.gov.ng)), then add it to your `.env`:

```dotenv
NIPOST_API_KEY=your-key
```

That's all you need. Every option can be set from `.env`, but if you want the config file in your app, publish it:

```bash
php artisan vendor:publish --tag=ng-postcode-config
```

If you cache your config, run `php artisan config:clear` after changing `.env`.

### Lookups

```php
use EmmanuelKolawole\NgPostcode\Facades\NgPostcode;
use EmmanuelKolawole\NgPostcode\Postcode;

$result = NgPostcode::lookup(Postcode::parse('EK-01-A03-FK-01'));

$result->valid;      // true or false
$result->status;     // NIPOST's status string, e.g. "not_found"
```

`lookup()` takes a `Postcode`, so the format is checked before any request is sent. Use `Postcode::tryParse()` first if the input comes from a user.

A postcode that's well formed but not assigned is a normal result, not an exception. You get `valid` as `false` and `status` as `"not_found"`:

```php
if (! $result->valid) {
    // $result->status === "not_found"
}
```

Exceptions are for when the lookup itself fails (see [Errors](#errors)). They never mean "this postcode doesn't exist".

### Levels and credits

NIPOST answers at five levels. Each level returns everything from the level below, plus more:

| Level | Adds                                                                 | Cost    |
|-------|----------------------------------------------------------------------|---------|
| 1     | Is the postcode valid?                                               | Free    |
| 2     | Administrative address (state, LGA, locality, zone), recent house address | Credits |
| 3     | Building use (residential, commercial, ...)                          | Credits |
| 4     | Other building information                                           | Credits |
| 5     | Coordinates                                                          | Credits |

Lookups use level 1 by default, so they're free. A higher level needs two things:

1. **Your app allows it.** Set `NIPOST_MAX_LEVEL` in `.env`. The default is `1`, so your app can't spend credits by accident.
2. **NIPOST has approved it for your organisation.** Request access under Settings → KYB & Access in the NIPOST dashboard. Raising `NIPOST_MAX_LEVEL` won't unlock a level NIPOST hasn't approved.

```dotenv
NIPOST_MAX_LEVEL=2
```

```php
$result = NgPostcode::lookup(Postcode::parse('EK-01-A03-FK-01'), level: 2);

$result->administrativeAddress->stateName;      // "EKITI"
$result->administrativeAddress->lgaName;        // "ADO EKITI"
$result->administrativeAddress->localityName;   // "ADO EKITI"
$result->administrativeAddress->zone;           // "SOUTH WEST"
$result->recentHouseAddress;                    // "NTA ROAD, BACK OF FABIAN HOTEL, ADO EKITI"
```

This is the example from NIPOST's quickstart. Names from lookups come back in uppercase. Names from `lgas()` (below) are mixed case.

`administrativeAddress` is `null` at level 1, and may be `null` when the postcode isn't found. Use `?->` if you're not sure:

```php
$result->administrativeAddress?->lgaName;       // null at level 1
```

The full response is always available as an array in `$result->raw`.

### Reference data

These read NIPOST's lists of LGAs, districts and areas. They don't cost credits.

```php
use EmmanuelKolawole\NgPostcode\State;

NgPostcode::lgas(State::FCT);
// ["01" => "Abaji", "02" => "...", ...]

NgPostcode::districts(State::FCT, '01');
// ["A01", "A02", ...]

NgPostcode::areas(State::FCT, '01', 'A01');
// ["KP", ...]

NgPostcode::lgaName(Postcode::parse('FC-01-A01-KP-27'));
// "Abaji" (or null if NIPOST doesn't list the LGA)
```

#### Example: state → LGA dropdown

Add a route that returns a state's LGAs. Laravel binds the `State` enum from the URL, so an unknown code returns a 404. The route is public, so throttle it. Reference calls are free and cached, but throttling is still good practice:

```php
use EmmanuelKolawole\NgPostcode\Facades\NgPostcode;
use EmmanuelKolawole\NgPostcode\State;

Route::get('/lgas/{state}', fn (State $state) => NgPostcode::lgas($state))
    ->middleware('throttle:60,1');
// GET /lgas/FC  →  {"01": "Abaji", "02": "...", ...}
```

Fill the LGA list when the state changes:

```blade
<select id="state">
    @foreach (\EmmanuelKolawole\NgPostcode\State::cases() as $state)
        <option value="{{ $state->value }}">{{ $state->label() }}</option>
    @endforeach
</select>

<select id="lga"></select>

<script>
document.getElementById('state').addEventListener('change', async (e) => {
    const lgas = await (await fetch(`/lgas/${e.target.value}`)).json();

    document.getElementById('lga').innerHTML = Object.entries(lgas)
        .map(([code, name]) => `<option value="${code}">${name}</option>`)
        .join('');
});
</script>
```

You can chain districts and areas the same way.

### Errors

| Exception               | Thrown when                                                                             | Extends                    |
|-------------------------|-----------------------------------------------------------------------------------------|----------------------------|
| `MissingApiKey`         | `NIPOST_API_KEY` is empty. No request is sent.                                          | `RuntimeException`         |
| `LookupLevelNotAllowed` | You ask for a level above `NIPOST_MAX_LEVEL`. No request is sent.                       | `InvalidArgumentException` |
| `InvalidApiKey`         | NIPOST rejects the key (HTTP 401).                                                      | `NipostException`          |
| `InsufficientCredits`   | Your account doesn't have enough credits for the lookup (HTTP 402).                     | `NipostException`          |
| `LevelNotGranted`       | NIPOST hasn't approved that level for your organisation (HTTP 403).                     | `NipostException`          |
| `RateLimitExceeded`     | Too many requests (HTTP 429). `$e->retryAfter` holds the seconds to wait, if NIPOST sent it. | `NipostException`     |
| `NipostUnavailable`     | Timeout, network error, or a NIPOST server error (HTTP 5xx).                            | `NipostException`          |
| `NipostException`       | Any other error response, or a response that isn't from the API (for example, a proxy's HTML page). | `RuntimeException` |

All exceptions are in the `EmmanuelKolawole\NgPostcode\Exceptions` namespace. Each message tells you how to fix the problem.

Catch `NipostException` to handle every API failure in one place:

```php
use EmmanuelKolawole\NgPostcode\Exceptions\NipostException;

try {
    $result = NgPostcode::lookup($postcode);
} catch (NipostException $e) {
    report($e);

    $e->httpStatus;   // 503 (0 if no response was received)
    $e->errorCode;    // NIPOST's error code, e.g. "level_not_granted", or null
}
```

`MissingApiKey` and `LookupLevelNotAllowed` are not `NipostException`s. They mean your app is set up wrong, not that the API failed, so let them surface and fix the config.

### Caching

Responses are cached in your app's default cache store, so the same request doesn't reach NIPOST twice.

| What                                 | Default         | `.env` setting               |
|--------------------------------------|-----------------|------------------------------|
| Reference data (LGAs, districts, areas) | 86400 (1 day) | `NIPOST_CACHE_REFERENCE_TTL` |
| Lookup results                       | 3600 (1 hour)   | `NIPOST_CACHE_LOOKUP_TTL`    |

```dotenv
NIPOST_CACHE_LOOKUP_TTL=86400   # keep lookups for a day
NIPOST_CACHE_ENABLED=false      # turn caching off, e.g. while debugging
```

Every cached lookup above level 1 is one you don't pay for, so a longer `lookup_ttl` saves credits. The trade-off is less fresh data.

- **`not_found` results are cached too**, for `lookup_ttl`. If NIPOST assigns a postcode after you looked it up, you'll keep getting `not_found` until the cache entry expires. Run `php artisan cache:clear` if you need it sooner.
- **Errors are never cached.** A failed request is tried again next time.
- **Each API key has its own cache.** Switching from a test key to a live key never returns sandbox answers from the cache.

### Test keys

NIPOST's documentation says test keys resolve sandbox postcodes such as `FC-01-A01-KP-27`. At the time of writing, the sandbox also returns `not_found` for that code, so expect `not_found` for most lookups with a test key. Real postcodes return `not_found` with a test key even when they're assigned.

```php
// With a test key, at the time of writing
NgPostcode::lookup(Postcode::parse('FC-01-A01-KP-27'))->status;   // "not_found"
```

Use a live key to check real postcodes. Test and live keys use the same URL, so to go live, put your live key in `NIPOST_API_KEY`. Nothing else changes.

## Good to know

- **Parsing, validation and the cast check the format only.** A well-formed code isn't necessarily assigned to a real building. Use [`NgPostcode::lookup()`](#lookups) to check with NIPOST.
- **Spaces, hyphens and letter case are ignored**, so `"ek 01 a03 fk 01"` is valid and becomes `EK-01-A03-FK-01`.
- **The cast is strict on write and forgiving on read.** Saving an invalid code throws an `InvalidArgumentException`. Reading an invalid value that's already in the database returns `null`, so old data won't crash your pages.
- **The old six-digit postcodes (like `100001`) are not accepted.** This package handles only the new digital format.
- LGA and building numbers run from `01` to `99`. `00` is rejected.
- **NIPOST's documentation calls the code 12 characters, but its own segments add up to 11** (2 + 2 + 3 + 2 + 2, e.g. `EK01A03FK01`). This package follows the segment format and expects 11.

## Roadmap

- **Autocomplete**, once NIPOST's sandbox supports it.

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
