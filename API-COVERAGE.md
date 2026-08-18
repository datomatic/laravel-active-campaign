# API coverage — what the package covers, and what it doesn't

Notes taken while preparing `v1.0.0`, measured against the
[ActiveCampaign API v3 reference](https://developers.activecampaign.com/reference).
Gaps 1.1 (pagination) and the fields gap in section 2 have since been closed; the rest still stands.

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

### 1.2 Query building

`list()` takes a raw query string. The API's filter syntax is verbose and easy to get wrong:

```
filters[created_after]=2024-01-01&orders[cdate]=DESC&include=contactTags&limit=100
```

A small fluent builder (`->filter('email', $x)->orderBy('cdate', 'DESC')->include('contactTags')`)
would remove most of the string juggling. Note that filter support differs per endpoint — contacts
also accept the legacy top-level `email=`, `search=`, `segmentid=`, `listid=`, `tagid=` params.

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

### 1.6 Testing helpers not exposed

Consumers have to hand-write `Http::fake(['*.api-us1.com/api/3/contacts' => ...])`. A shipped
`ActiveCampaign::fake()` / response factory would be a small, high-value addition.

---

## 2. Endpoint coverage of the resources we *do* wrap

### Contacts — partial

Covered: list/search, retrieve, create, update, delete, sync, contact tags (list/add/remove), list
subscription status.

Missing:

| Endpoint | Notes |
|---|---|
| `GET /contacts/{id}/contactLists` | read back which lists a contact is on — we can only write |
| `GET /contacts/{id}/fieldValues` | a contact's custom field values, without a full retrieve |
| `GET /contacts/{id}/contactAutomations` | automations the contact is in |
| `POST /contactAutomations` / `DELETE /contactAutomations/{id}` | add/remove a contact to an automation |
| `GET /contacts/{id}/contactDeals` | deals of a contact |
| `GET /contacts/{id}/geoIps`, `/trackingLogs`, `/scoreValues`, `/bounceLogs`, `/contactData` | activity & enrichment data |
| `GET /contacts/{id}/organization` | CRM account of the contact |
| `POST /import/bulk_import` | bulk import, up to 250 contacts per call — the right tool for big syncs |
| `GET /import/info`, `GET /import/bulk_import/{batchId}/status` | bulk import status |
| `DELETE /contacts/bulk_delete` | bulk delete |

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

### Field values — complete

Covered: list, retrieve, create, update, delete.
Missing: the `useDefaults` flag on create/update, which populates other required fields with their
defaults. Minor.

---

## 3. Resource groups not wrapped at all

Roughly ordered by how often they come up.

| Group | Endpoints | Note |
|---|---|---|
| **Lists** | `/lists`, `/lists/{id}/contactGoalLists`, `/listGroups` | conspicuous omission — we can subscribe a contact to a list id, and relate a field to one, but not discover, create or manage lists |
| **Deals (CRM)** | `/deals`, `/dealStages`, `/dealGroups` (pipelines), `/dealCustomFieldMeta`, `/dealCustomFieldData`, `/notes`, `/dealTasks`, `/dealTasktypes` | the whole CRM half of the product |
| **Accounts (CRM)** | `/accounts`, `/accountContacts`, `/accountCustomFieldMeta`, `/accountCustomFieldData` | B2B/organization records |
| **Automations** | `/automations`, `/contactAutomations` | triggering an automation for a contact is a very common integration need |
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
3. **Lists** resource — small, and closes the loop with `updateListStatus()`. Next up.
4. **Automations** (`/contactAutomations`) — high demand, tiny surface.
5. **1.2 query builder** + **1.6 test helpers** — developer experience.
6. **Bulk import** (`/import/bulk_import`) — the correct answer to "sync 10 000 contacts", which
   today means 10 000 requests against a 5 req/s limit.
7. Deals / Accounts, as a second wave.

## 5. Things deliberately not done

- **DTOs / typed responses.** Arrays keep the wrapper thin and forward-compatible with API changes.
  Revisit only if the package grows past ~10 resources.
- **Ecommerce Deep Data.** Big enough to deserve `datomatic/laravel-active-campaign-ecommerce`.
- **Site tracking.** Different host, different credentials (`actid` + event key); mixing it into the
  same client would muddy the config.
