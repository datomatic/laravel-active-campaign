# Changelog

All notable changes to `laravel-active-campaign` will be documented in this file.

## v1.0.0 - 2026-08-18

First stable release. It contains breaking changes over `0.2.x`, all of them fixes to calls that
could not have worked against the real API.

### Fixed

- `contacts()->sync()` posted to `contacts/sync`; the endpoint is `contact/sync`.
- `contacts()->create()`/`sync()` sent `fieldValues` as a top-level key; the API expects it nested
  inside the `contact` object, so custom fields were silently dropped.
- `contacts()->getContactTagId()` looked for a `tag_id` key that the API never returns (it is `tag`),
  so `untag()` and `tryUntag()` never removed anything.
- `contacts()->untag()` built an `ActiveCampaignException` without throwing it.
- `contacts()->updateListStatus()` sent a `contactLists` array; the API accepts one `contactList`
  object per request.
- `fieldValues()->createFieldValue()` omitted the required `contact` id.
- `fields()->createField()`/`updateFieldValue()` were copies of the fieldValues methods and sent a
  `field`/`value` pair instead of a field definition.
- `ActiveCampaignConfig::timeout()` was declared as returning `string`.
- Retries were applied to every failed response, including `4xx`, and surfaced as
  `Illuminate\Http\Client\RequestException` instead of `ActiveCampaignException`.
- `GET` and `DELETE` requests no longer send an empty JSON body.
- The client is no longer a singleton: a `PendingRequest` is stateful and must not be reused.

### Changed

- Requires PHP 8.2+ and Laravel 10, 11 or 12.
- Every failing request now throws `ActiveCampaignException` only; `RequestException` no longer
  leaks out of the package.
- `fields()->createField(string $title, FieldType $type, array $attributes)` and
  `fields()->updateField(int $id, string $title, FieldType $type, array $attributes)` replace the
  previous signatures.
- `fieldValues()->createFieldValue(int $contactId, int $fieldId, string $value)` takes the contact id.
- `list()` dropped its unused `$responseKey` argument; resources declare their own response key.
- `retry_sleep` now defaults to `1000` ms (was `5`).
- Only connection errors, `429` and `5xx` responses are retried.

### Added

- `ListStatus`, `FieldType` and `TagType` enums.
- `tags()->createTag()`/`updateTag()` accept a `TagType`.
- Full Pest test suite and PHPStan level 8.
- A real README.

## v0.3.0 - 2026-03-17

Laravel 13 support

## v0.2.3 - 2025-07-22

- fix

## v0.2.2 - 2025-04-12

Laravel 12

## v0.2.0 - 2025-02-24

- Laravel 12 support

## v0.1.3 - 2025-01-20

- add static methods PHP Docs on facade

## v0.1.2 - 2025-01-18

fix config and views

## v0.1.1 - 2025-01-07

- fix empty config value exception

## v0.1.0 - 2025-01-02

- first release
