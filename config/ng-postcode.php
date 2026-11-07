<?php

/*
|--------------------------------------------------------------------------
| NIPOST Digital Postcode
|--------------------------------------------------------------------------
|
| Settings for talking to the NIPOST postcode API (api.postcode.gov.ng).
|
| Parsing, validating and formatting postcodes work offline and don't read
| anything from this file. These options are only for API lookups.
|
| Each option can be set from your .env file, so you rarely need to publish
| and edit this file.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your NIPOST API key, sent with every request. Keep it in .env and never
    | commit it. If it is empty, API lookups can't be made.
    |
    */

    'api_key' => env('NIPOST_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Address of the NIPOST API. Test keys and live keys both use this same
    | URL, and there is no separate sandbox URL. To test, switch to a test
    | key in NIPOST_API_KEY and leave this as it is.
    |
    | Only change it to point at a mock server or a proxy, for example in
    | your own tests.
    |
    */

    'base_url' => env('NIPOST_BASE_URL', 'https://api.postcode.gov.ng'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | How many seconds to wait for the API before giving up. Keep it short so
    | a slow API doesn't hold up your own requests.
    |
    */

    'timeout' => (int) env('NIPOST_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Maximum Lookup Level
    |--------------------------------------------------------------------------
    |
    | The highest NIPOST lookup level your app may request. Each level returns
    | everything the level below it returns, plus more:
    |
    |   1  Is the postcode valid?                                    Free
    |   2  + administrative address (state, LGA, locality, zone)
    |        and recent house address                                Credits
    |   3  + building use (residential, commercial, ...)             Credits
    |   4  + other building information                              Credits
    |   5  + coordinates                                             Credits
    |
    | Level 1 is free. Every level above it spends credits on each lookup.
    | The default of 1 keeps lookups free until you choose to go higher.
    |
    | Your API key can only go as high as the level NIPOST granted your
    | organisation. Setting a higher value here won't unlock more.
    |
    */

    'max_level' => (int) env('NIPOST_MAX_LEVEL', 1),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | API responses are cached with your app's default cache store, so the
    | same request isn't sent to NIPOST twice.
    |
    | enabled:       set to false to call the API every time (useful when
    |                debugging).
    |
    | reference_ttl: how long reference data (LGAs, districts, areas) stays
    |                cached, in seconds. It almost never changes, so the
    |                default is 86400 (one day). You can safely raise it.
    |
    | lookup_ttl:    how long postcode lookup results stay cached, in
    |                seconds. NIPOST assigns and updates buildings over time,
    |                so the default is shorter: 3600 (one hour). Lookups
    |                above level 1 cost credits, and every cache hit is a
    |                lookup you don't pay for, so a longer value saves money
    |                at the cost of fresher data.
    |
    */

    'cache' => [
        'enabled' => env('NIPOST_CACHE_ENABLED', true),
        'reference_ttl' => (int) env('NIPOST_CACHE_REFERENCE_TTL', 86400),
        'lookup_ttl' => (int) env('NIPOST_CACHE_LOOKUP_TTL', 3600),
    ],

];
