# API coverage — what the package covers, and what it doesn't

Notes taken while preparing `v1.0.0`, measured against the
[ActiveCampaign API v3 reference](https://developers.activecampaign.com/reference).
Most of it has since been closed: pagination, query building, test helpers, field options and
relations, lists, automations, bulk import, and the core of deals and accounts. Each section says
what is left.

Nothing here blocks the 1.0 release: the package is honest about being a wrapper around four
resources, and `request()` is public so any endpoint is reachable. This is the roadmap for 1.x.

---

## 1. Cross-cutting gaps (higher impact than any single endpoint)

These affect endpoints that are *already* wrapped, so they are the ones worth doing first.

### 1.1 Pagination — done in 1.0

ActiveCampaign returns **20 records by default, 100 maximum**, plus a `meta` object:

```json
{ "contacts": [ ... ], "meta": { "total": "4312", "page_input": { ... } } }
```

`list()` used to return the first page only and threw `meta` away, so a caller with 4 000 contacts
silently got 20 with no way to know. Now covered by:

- `list($query, $limit, $offset)` — one explicit page.
- `count($query)` — reads `meta.total`.
- `paginate($perPage, $page, $query)` — `LengthAwarePaginator`, usable in a view.
- `lazy($query, $perPage)` / `all($query, $perPage)` — walk every page.
- Contacts walk by `id_greater` + `orders[id]=ASC` rather than by offset, as the API recommends,
  falling back to offset when the caller sets its own ordering or id bound.

Still open:

- `id_less` / descending cursor walks.
- `paginate()` has no cursor variant, so deep page numbers on contacts remain offset-based and slow.
  A `cursorPaginate()` would fix that, at the cost of losing addressable page numbers.

### 1.2 Query building — done in 1.0

`Datomatic\ActiveCampaign\Support\Query` builds the `filters[...]` / `orders[...]` syntax, is
accepted anywhere a query string is, and normalises booleans, backed enums, arrays and dates.
`where()` covers the top-level params the API defines outside `filters` (contacts' `email`,
`search`, `segmentid`, `listid`, `tagid`, `id_greater`).

Still open: filter support differs per endpoint and the builder does not know which fields each
endpoint accepts, so a wrong field name is still only caught by the API.

### 1.3 Rate limiting

ActiveCampaign allows **5 requests/second per account**. The package retries a `429`, but:

- it does not read the `Retry-After` header, it just sleeps `retry_sleep`;
- there is no client-side throttle, so a loop over `updateListStatus()` or an `untag()` batch can
  trip the limit by itself.

### 1.4 Everything is an array

No DTOs, no typed properties, no IDE completion on results. Acceptable for 1.0 (and keeps the
package thin), but it is the reason PHPStan stops at level 8 rather than level 9/10.

### 1.5 No events

Nothing is dispatched, so consumers cannot hook into "contact synced" / "request failed" without
wrapping every call. Laravel's own `Illuminate\Http\Client\Events\ResponseReceived` fires, but it is
not scoped to this package.

### 1.6 Testing helpers — done in 1.0

`Datomatic\ActiveCampaign\Testing\ActiveCampaignFake` fakes by path relative to `/api/3`, builds
list/single/error envelopes, and asserts on method, path and JSON body.

---

## 2. Endpoint coverage of the resources we *do* wrap

### Contacts — partial

Covered: list/search, retrieve, create, update, delete, sync, contact tags (list/add/remove), list
subscriptions (read and write), automation enrolments (list/add/remove).

Missing:

| Endpoint | Notes |
|---|---|
| `GET /contacts/{id}/fieldValues` | a contact's custom field values, without a full retrieve |
| `GET /contacts/{id}/contactDeals` | deals of a contact |
| `GET /contacts/{id}/geoIps`, `/trackingLogs`, `/scoreValues`, `/bounceLogs`, `/contactData` | activity & enrichment data |
| `GET /contacts/{id}/organization` | CRM account of the contact |
| `DELETE /contacts/bulk_delete` | bulk delete |
| `POST /contacts/bulk_edit` | bulk tag/list edits |

`sync()` also ignores the `tags` key that the API accepts inside `contact` (additive, auto-creates
tags) — supporting it would remove the separate `tag()` round-trip in the common case.

### Tags — essentially complete

Covered: list, retrieve, create, update, delete, both `tagType`s.
Missing: nothing meaningful. Listing a tag's contacts is done through the contacts endpoint
(`contacts?tagid=`), which already works.

### Fields — done in 1.0

Covered: list, retrieve, create, update, delete of the field definition, plus the two things that
make a field actually usable:

| Endpoint | Wrapped as |
|---|---|
| `POST /fieldOption/bulk` | `fields()->createOptions()`, `fieldOptions()->createMany()` |
| `GET /fields/{id}/options` | `fields()->options()` |
| `POST /fieldRels` | `fields()->relate()`, `fieldRels()->relate()` |
| `GET /fields/{id}/relations` | `fields()->relations()` |

`fields()->createField()` can create the field, its options and its list relations in one call, and
`FieldType::requiresOptions()` names the types that need options.

Still open:

- `update()` and `delete()` on `fieldOptions` / `fieldRels` are inherited from the base resource but
  are not in the published reference, so they are untested against real responses. Treat them as
  unsupported until confirmed.
- `relid: 0` for "all lists" is what the app sends, but it is not documented. `relate()` defaults to
  it via `ActiveCampaignFieldRelsResource::ALL_LISTS`; pass real list ids when you know them.
- `GET /fields/{id}` already returns `fieldOptions` and `fieldRels` alongside `field`, and `get()`
  discards them. `options()` / `relations()` each cost an extra request as a result.

### Bulk import — done in 1.0

Covered: `POST /import/bulk_import` (`import()->bulk()`, with `bulkAll()` chunking at the API's
250-contact ceiling and accepting a `LazyCollection`), `GET /import/info` (`status()`, `statusOf()`
returning a `BulkImportStatus`) and `GET /import/bulk_import` (`info()`).

The importer names contact fields differently from the rest of the API (`first_name`, not
`firstName`; `fields: [{id, value}]`, not `fieldValues: [{field, value}]`), so the resource accepts
both spellings and translates.

Still open: `DELETE /contacts/bulk_delete` and `POST /contacts/bulk_edit`.

### Lists — done in 1.0

Covered: list (with `filters[name]`), retrieve, create, delete, plus `contacts()->lists()` for a
contact's subscriptions, which closes the loop with `updateListStatus()`.

Still open:

- `update()` is inherited but ActiveCampaign documents no PUT for lists, so it is unverified.
- `/lists/{id}/contactGoalLists` and `/listGroups` (list group permissions) are not wrapped.

### Automations — done in 1.0

Covered: `GET /automations` and `GET /automations/{id}`, the `/contactAutomations` resource, and
`contacts()->automations()`, `addToAutomation()`, `removeFromAutomation()`,
`tryRemoveFromAutomation()`, `getContactAutomationId()`.

Still open:

- The API documents no create, update or delete for automations themselves — they are built in the
  ActiveCampaign UI. `automations()->create()`/`update()`/`delete()` are inherited from the base
  resource and will fail against the API; treat the resource as read only.
- `/automations` `meta` also carries `starts`, `filtered` and `smsLogs`, which `list()` discards.

### Deals and accounts — done in 1.0

Covered: `/deals`, `/dealStages`, `/dealGroups` (exposed as `pipelines()`), `/accounts`,
`/accountContacts` and `/notes`, each with the full CRUD surface plus a typed constructor
(`createDeal()`, `createStage()`, `createPipeline()`, `createAccount()`, `associate()`,
`createNote()` with a `NoteRelType`).

Still open in the CRM area:

- `/dealCustomFieldMeta` and `/dealCustomFieldData`, and their account equivalents
  `/accountCustomFieldMeta` and `/accountCustomFieldData`, including their bulk endpoints.
- `/dealTasks` and `/dealTasktypes`.
- `/dealActivities`, and deal roles.
- `createDeal()` does not check that the stage belongs to the pipeline; the API does.

### Field values — complete

Covered: list, retrieve, create, update, delete.
Missing: the `useDefaults` flag on create/update, which populates other required fields with their
defaults. Minor.

---

## 3. Resource groups not wrapped at all

Roughly ordered by how often they come up.

| Group | Endpoints | Note |
|---|---|---|
| **Campaigns** | `/campaigns`, `/campaigns/{id}/links`, `/messages` | |
| **Webhooks** | `/webhooks`, `/webhook/events`, `/webhook/listeners` | plus no signature verification / event-to-listener helper on our side |
| **Ecommerce (Deep Data)** | `/ecomOrders`, `/ecomCustomers`, `/ecomOrderProducts`, `/ecomOrderActivities`, `/connections` | large surface, separate concern — arguably its own package |
| **Segments** | `/segments` | |
| **Forms** | `/forms` | |
| **Templates** | `/templates` | |
| **Users & Groups** | `/users`, `/groups`, `/userGroups` | account administration |
| **Addresses** | `/addresses`, `/addressGroups`, `/addressLists` | required for compliance footers |
| **Scores** | `/scores` | contact & deal scoring rules |
| **Saved responses, Calendar feeds, Branding, Task outcomes, Configs, SMS, Site messages** | | rarely needed |
| **Site & event tracking** | `trackcmp.net/event`, `/whitelist`, `/status` | **different host and a different auth scheme** — would need its own client, not just a new resource |

---

## 4. Suggested order of work after 1.0

1. ~~**1.1 pagination**~~ — done in 1.0.
2. ~~**`fieldRels` / `fieldOptions`**~~ — done in 1.0.
3. ~~**Lists** resource~~ — done in 1.0.
4. ~~**Automations** (`/contactAutomations`)~~ — done in 1.0.
5. ~~**1.2 query builder** + **1.6 test helpers**~~ — done in 1.0.
6. ~~**Bulk import** (`/import/bulk_import`)~~ — done in 1.0.
7. ~~Deals / Accounts~~ — the core is done in 1.0. What is left there (deal and account custom
   fields, tasks, activities, roles) is listed under "Deals and accounts" above.

Everything originally listed as a gap is now either closed or narrowed to a named leftover. The
largest untouched areas remain campaigns, webhooks, ecommerce deep data, segments, forms, users
and site tracking — see section 3.

## 5. Things deliberately not done

- **DTOs / typed responses.** Arrays keep the wrapper thin and forward-compatible with API changes.
  Revisit only if the package grows past ~10 resources.
- **Ecommerce Deep Data.** Big enough to deserve `datomatic/laravel-active-campaign-ecommerce`.
- **Site tracking.** Different host, different credentials (`actid` + event key); mixing it into the
  same client would muddy the config.
