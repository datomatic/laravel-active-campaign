# Laravel wrapper for ActiveCampaign API v3

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datomatic/laravel-active-campaign.svg?style=flat-square)](https://packagist.org/packages/datomatic/laravel-active-campaign)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datomatic/laravel-active-campaign/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datomatic/laravel-active-campaign/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/datomatic/laravel-active-campaign/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/datomatic/laravel-active-campaign/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/datomatic/laravel-active-campaign.svg?style=flat-square)](https://packagist.org/packages/datomatic/laravel-active-campaign)

A small, explicit Laravel wrapper around the [ActiveCampaign API v3](https://developers.activecampaign.com/reference).
It builds on Laravel's HTTP client, so you keep timeouts, retries, `Http::fake()` and the rest of the
framework's tooling, while the package takes care of authentication, the `/api/3` base path, the request/response
envelopes ActiveCampaign uses (`contact`, `tag`, `field`, `fieldValue`, …) and error handling.

```php
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;

$contact = ActiveCampaign::contacts()->sync([
    'email' => 'john@example.com',
    'firstName' => 'John',
    'lastName' => 'Doe',
]);

ActiveCampaign::contacts()->tag($contact['id'], 5);
```

## Requirements

- PHP 8.2+
- Laravel 10, 11 or 12

## Installation

```bash
composer require datomatic/laravel-active-campaign
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-active-campaign-config"
```

Then add your credentials to `.env`:

```dotenv
ACTIVE_CAMPAIGN_BASE_URL=https://your-account.api-us1.com
ACTIVE_CAMPAIGN_API_KEY=your-api-key
```

> The base URL is your account URL **without** the `/api/3` suffix — the package appends it.
> You can find both values in your ActiveCampaign account under *Settings → Developer*.

### Configuration

```php
return [
    'base_url' => env('ACTIVE_CAMPAIGN_BASE_URL'),

    'api_key' => env('ACTIVE_CAMPAIGN_API_KEY'),

    // Request timeout, in seconds.
    'timeout' => 100,

    // How many times a request is attempted before giving up.
    // Only connection errors, 429 and 5xx responses are retried.
    'retry_times' => 3,

    // How long to wait between two attempts, in milliseconds.
    'retry_sleep' => 1000,

    // Map your ActiveCampaign custom field ids to the names you want to use in your code.
    'custom_fields' => [
        // 'is_email_verified' => 50,
    ],
];
```

## Usage

Every resource is reachable from the `ActiveCampaign` facade (or by injecting
`Datomatic\ActiveCampaign\ActiveCampaign`):

| Method | Resource | ActiveCampaign endpoints |
|---|---|---|
| `ActiveCampaign::contacts()` | contacts, contact tags, list subscriptions | `/contacts`, `/contact/sync`, `/contactTags`, `/contactLists` |
| `ActiveCampaign::lists()` | contact lists | `/lists` |
| `ActiveCampaign::automations()` | automations (read only) | `/automations` |
| `ActiveCampaign::contactAutomations()` | contact ↔ automation enrolments | `/contactAutomations` |
| `ActiveCampaign::tags()` | tags | `/tags` |
| `ActiveCampaign::fields()` | custom field definitions, their options and list relations | `/fields`, `/fieldOption/bulk`, `/fieldRels` |
| `ActiveCampaign::fieldOptions()` | selectable values of a field | `/fieldOptions`, `/fieldOption/bulk` |
| `ActiveCampaign::fieldRels()` | field ↔ list relations | `/fieldRels` |
| `ActiveCampaign::fieldValues()` | custom field values of a contact | `/fieldValues` |

All of them share the same CRUD surface:

```php
ActiveCampaign::tags()->list();                    // Collection<int, array> — first page only
ActiveCampaign::tags()->list('filters[tagType]=contact');
ActiveCampaign::tags()->get(1);                    // array
ActiveCampaign::tags()->create([...]);             // array
ActiveCampaign::tags()->update(1, [...]);          // array
ActiveCampaign::tags()->delete(1);                 // void
```

Responses are returned as plain arrays, already unwrapped from the ActiveCampaign envelope and
stripped of the `links` key.

### Pagination

The API returns **20 records per page by default and 100 at most**, so `list()` alone will quietly
give you a slice of a large collection. Four methods cover the rest:

```php
ActiveCampaign::contacts()->list(limit: 100, offset: 200); // one page, explicitly
ActiveCampaign::contacts()->count();                       // total matching records
ActiveCampaign::contacts()->paginate(perPage: 50);         // LengthAwarePaginator, for views
ActiveCampaign::contacts()->lazy();                        // LazyCollection over every page
ActiveCampaign::contacts()->all();                         // Collection over every page
```

`lazy()` fetches one page at a time and only when you consume it, so it is the safe way to walk a
large account:

```php
ActiveCampaign::contacts()->lazy()
    ->filter(fn (array $contact) => $contact['email'])
    ->each(fn (array $contact) => ProcessContact::dispatch($contact));
```

`paginate()` returns Laravel's `LengthAwarePaginator`, so `->links()` works in a Blade view:

```php
$contacts = ActiveCampaign::contacts()->paginate(perPage: 50);

$contacts->total();       // from the API's meta.total
$contacts->lastPage();
$contacts->items();
```

A `perPage` above 100 is clamped to 100, and `count()` reads the `meta.total` the API sends back
(it returns `0` for the few endpoints that do not report one).

All of them accept the same query string as `list()`, and it is applied to every page:

```php
ActiveCampaign::contacts()->count('filters[created_after]=2024-01-01');
ActiveCampaign::contacts()->all('filters[created_after]=2024-01-01');
```

> **Note on the query string.** It is parsed and re-encoded so that pagination params can be merged
> into it, so `email=john@example.com` goes out as `email=john%40example.com`. When the raw query and
> an explicit `limit`/`offset` argument set the same key, the argument wins.

#### Contacts are walked by id

ActiveCampaign [recommends](https://developers.activecampaign.com/reference/list-all-contacts)
paginating contacts with `id_greater` rather than `offset`, because a deep offset on a large account
is slow and can skip records while the list shifts underneath the walk. `contacts()->lazy()` and
`contacts()->all()` do that for you, adding `orders[id]=ASC&id_greater=<last id>` to each page.

If your query already sets `orders[...]`, `id_greater` or `id_less`, the generic offset walk is used
instead so your ordering is preserved. `paginate()` is always offset-based, since it needs
addressable page numbers.

### Contacts

```php
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;

// List / search / filter — the query string is passed through to the API
$contacts = ActiveCampaign::contacts()->list('email=john@example.com');

$contact = ActiveCampaign::contacts()->get(1);

// Create — 'email' is required, unknown keys are dropped
$contact = ActiveCampaign::contacts()->create([
    'email' => 'john@example.com',
    'firstName' => 'John',
    'lastName' => 'Doe',
    'phone' => '+39 000 000 0000',
]);

// Create or update by email in a single call
$contact = ActiveCampaign::contacts()->sync([
    'email' => 'john@example.com',
    'firstName' => 'John',
]);

ActiveCampaign::contacts()->update(1, ['email' => 'john@example.com', 'lastName' => 'Doe']);

ActiveCampaign::contacts()->delete(1);
```

`create()`, `update()` and `sync()` accept only `email`, `firstName`, `lastName`, `phone` and the
custom field names you declared in the config. Everything else is ignored, so you can hand them a
model's `toArray()` without filtering it first. All three require `email` and throw an
`ActiveCampaignException` without it.

#### Custom fields

ActiveCampaign identifies custom fields by numeric id. Map them once in the config file:

```php
'custom_fields' => [
    'is_email_verified' => 50,
    'city' => 51,
],
```

and then use their names on both sides of the call:

```php
$contact = ActiveCampaign::contacts()->sync([
    'email' => 'john@example.com',
    'is_email_verified' => '1',
    'city' => 'Rome',
]);

$contact['city']; // 'Rome'
```

Empty values are skipped, so a field you do not pass is never overwritten with an empty string.

#### Tags on a contact

```php
ActiveCampaign::contacts()->tags(1);        // the contactTag rows of contact 1
ActiveCampaign::contacts()->tag(1, 5);      // apply tag 5 to contact 1
ActiveCampaign::contacts()->untag(1, 5);    // throws if the contact is not tagged
ActiveCampaign::contacts()->tryUntag(1, 5); // no-op if the contact is not tagged

ActiveCampaign::contacts()->getContactTagId(1, 5); // ?int
```

Removing a tag needs the id of the *association*, not of the tag, so `untag()` and `tryUntag()`
resolve it for you with an extra `GET` before the `DELETE`.

#### List subscriptions

```php
use Datomatic\ActiveCampaign\Enums\ListStatus;

ActiveCampaign::contacts()->lists(1);   // current subscriptions, with their status

ActiveCampaign::contacts()->updateListStatus(1, [
    2 => ListStatus::Subscribed,
    3 => ListStatus::Unsubscribed,
]);
```

The array is keyed by list id. Plain integers (`1` / `2`) are accepted as well. One request per
list is sent, because the API only accepts a single `contactList` object per call.

#### Automations

```php
ActiveCampaign::contacts()->automations(1);              // automations the contact is in
ActiveCampaign::contacts()->addToAutomation(1, 42);
ActiveCampaign::contacts()->removeFromAutomation(1, 42); // throws if the contact is not in it
ActiveCampaign::contacts()->tryRemoveFromAutomation(1, 42);

ActiveCampaign::contacts()->getContactAutomationId(1, 42); // ?int
```

As with tags, removing needs the id of the *enrolment* rather than of the automation, so the
remove methods resolve it with an extra `GET` before the `DELETE`.

### Lists

```php
ActiveCampaign::lists()->list();
ActiveCampaign::lists()->list('filters[name]=Newsletter');
ActiveCampaign::lists()->get(1);

ActiveCampaign::lists()->createList(
    name: 'Newsletter',
    stringId: 'newsletter',
    senderUrl: 'https://example.com',
    senderReminder: 'You subscribed on our website.',
);

ActiveCampaign::lists()->delete(1);
```

The four arguments of `createList()` are the ones the API requires; a fifth array is merged into
the `list` object for anything else (`channel`, `user`, `send_last_broadcast`, …).

> ActiveCampaign does not document an update endpoint for lists, so `lists()->update()` is
> inherited but unverified. See [API-COVERAGE.md](API-COVERAGE.md).

### Automations

Automations themselves are read only in the API — you build them in the ActiveCampaign UI and
enrol contacts through the API:

```php
ActiveCampaign::automations()->list();
ActiveCampaign::automations()->get(42);

ActiveCampaign::contactAutomations()->add(1, 42);   // same as contacts()->addToAutomation()
ActiveCampaign::contactAutomations()->list('filters[automation]=42');
```

### Tags

```php
use Datomatic\ActiveCampaign\Enums\TagType;

ActiveCampaign::tags()->list();
ActiveCampaign::tags()->createTag('customer', 'a paying user');
ActiveCampaign::tags()->createTag('header', '', TagType::Template);
ActiveCampaign::tags()->updateTag(1, 'customer', 'updated description');
ActiveCampaign::tags()->delete(1);
```

`tagType` defaults to `contact` when you do not pass one.

### Fields

```php
use Datomatic\ActiveCampaign\Enums\FieldType;

ActiveCampaign::fields()->list();

ActiveCampaign::fields()->createField('City');
ActiveCampaign::fields()->createField('Birthday', FieldType::Date, [
    'perstag' => 'BIRTHDAY',
    'visible' => 1,
]);

ActiveCampaign::fields()->updateField(1, 'Town', FieldType::Text);
ActiveCampaign::fields()->delete(1);
```

The third argument is merged into the `field` object, so any attribute the API supports
(`descript`, `perstag`, `defval`, `visible`, `ordernum`, `isrequired`, …) can be passed through.

#### A field is not usable on its own

Two things are easy to miss, and the API will not warn you about either:

1. **A custom field stays invisible until it is related to a list.** A contact only sees a field if
   one of the lists it belongs to has a relation to that field.
2. **Dropdown, listbox, radio, checkbox and multiselect fields need their options created
   separately.** `FieldType::requiresOptions()` tells you which types those are.

`createField()` can do all three steps in one call:

```php
$field = ActiveCampaign::fields()->createField(
    'Department',
    FieldType::DropDown,
    options: ['Sales', 'Engineering', 'Support'],
    lists: [1, 2],
);
```

That creates the field, bulk-creates its options in the order given, and relates it to lists 1 and 2.
Omit `options`/`lists` and nothing extra is sent.

Each step is also available on its own:

```php
ActiveCampaign::fields()->createOptions(34, ['Sales', 'Engineering']);
ActiveCampaign::fields()->options(34);      // Collection of the field's options

ActiveCampaign::fields()->relate(34, 1);    // relate field 34 to list 1
ActiveCampaign::fields()->relate(34);       // relate it to every list
ActiveCampaign::fields()->relations(34);    // Collection of the field's list relations
```

Options accept plain strings, or full arrays when you need more control. A string becomes an option
whose `label` and `value` match, and `orderid` follows the array order unless you set it yourself:

```php
ActiveCampaign::fields()->createOptions(34, [
    ['value' => 'sales', 'label' => 'Sales', 'isdefault' => true],
    ['value' => 'eng', 'label' => 'Engineering'],
]);
```

Options and relations have their own resources too, if you want to work with them directly:

```php
ActiveCampaign::fieldOptions()->createMany([
    ['field' => 34, 'value' => 'Sales', 'label' => 'Sales', 'orderid' => 1],
]);

ActiveCampaign::fieldRels()->relate(34, 1);
```

> ActiveCampaign creates options only through its bulk endpoint, so
> `fieldOptions()->create()` sends a one-element bulk request and hands you back the created option.

### Field values

```php
// Set custom field 3 of contact 7
ActiveCampaign::fieldValues()->createFieldValue(7, 3, 'Rome');

ActiveCampaign::fieldValues()->updateFieldValue(1, 3, 'Milan');

ActiveCampaign::fieldValues()->list('filters[fieldid]=3');
ActiveCampaign::fieldValues()->delete(1);
```

For contacts you own, `contacts()->sync()` with the `custom_fields` mapping is usually the shorter
path — this resource is there for the cases where you need to address a field value directly.

### Error handling

Every failing response raises a `Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException`
carrying the endpoint and the error body returned by ActiveCampaign:

```php
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

try {
    ActiveCampaign::contacts()->get(999999);
} catch (ActiveCampaignException $e) {
    // The request to "contacts/999999" generated this error: [{"title":"No Result found ..."}]
    report($e);
}
```

A misconfigured package raises `Datomatic\ActiveCampaign\Exceptions\InvalidConfig` instead, on the
first call that needs the missing value.

Connection errors, `429` and `5xx` responses are retried according to `retry_times` / `retry_sleep`
before the exception is raised. `4xx` responses are not retried.

### Escape hatches

`request()` is public on every resource, so an endpoint the package does not wrap yet is still one
line away:

```php
use Datomatic\ActiveCampaign\Enums\Method;

$lists = ActiveCampaign::contacts()->request(
    method: Method::GET,
    path: 'lists',
    responseKey: 'lists',
);
```

You can also resolve the client directly and skip the resource layer entirely:

```php
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;

$response = resolve(ActiveCampaignClientContract::class)
    ->send(Method::GET, 'campaigns'); // Illuminate\Http\Client\Response
```

### Testing your own code

The package uses Laravel's HTTP client, so `Http::fake()` works as usual:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*.api-us1.com/api/3/contacts' => Http::response(['contacts' => []]),
]);
```

## Testing

```bash
composer test
composer analyse
composer format
```

## Roadmap

[API-COVERAGE.md](API-COVERAGE.md) lists exactly which ActiveCampaign endpoints this package wraps,
which it doesn't, and what is planned for 1.x.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alberto Peripolli](https://github.com/tr1pp0)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
