# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [0.2.0] - 2026-11-07

Upgrading from 0.1 is safe. Parsing, validation, the string rule and the Eloquent cast work exactly as before, and the new API features do nothing until you set `NIPOST_API_KEY`.

### Added

- NIPOST API client (`NipostClient`) and an `NgPostcode` facade, registered automatically.
- `lookup()` checks a postcode with NIPOST and returns a `LookupResult`. A postcode that isn't assigned is a normal result (`valid: false`, `status: "not_found"`), not an exception.
- Lookup levels 1–5. Level 1 is free and is the default. Higher levels spend credits and must be allowed with `NIPOST_MAX_LEVEL`.
- `AdministrativeAddress` for level 2 results: state, LGA, locality and zone.
- Reference data: `lgas()`, `districts()`, `areas()` and `lgaName()`.
- Caching for reference data (1 day) and lookups (1 hour), configurable with `NIPOST_CACHE_*`. Errors are never cached, and each API key has its own cache.
- Exceptions for API failures, all extending `NipostException`: `InvalidApiKey`, `InsufficientCredits`, `LevelNotGranted`, `RateLimitExceeded` and `NipostUnavailable`. Two configuration errors, `MissingApiKey` and `LookupLevelNotAllowed`, are thrown before any request is sent.
- A config file, `config/ng-postcode.php`. Publish it with `php artisan vendor:publish --tag=ng-postcode-config`.

### Changed

- New required dependency: `illuminate/http`, for the API client.
- `illuminate/validation` moved from `require-dev` to `require`. The validation rule already needed it, so apps that don't install it themselves no longer depend on another package pulling it in.

## [0.1.0] - 2026-11-07

### Added

- Initial release: `Postcode` value object, `State` enum, `NigerianPostcode` validation rule, `ng_postcode` string rule and `AsPostcode` Eloquent cast.

[0.2.0]: https://github.com/EmmyConceptNg/laravel-ng-postcode/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/EmmyConceptNg/laravel-ng-postcode/releases/tag/v0.1.0
