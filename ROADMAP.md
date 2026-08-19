# Roadmap

What is still missing after `v1.0.0`, and in what order it is worth doing.

[API-COVERAGE.md](API-COVERAGE.md) is the reference for what the package *does* cover, endpoint by
endpoint. This file is only the work that remains. Nothing here blocks 1.0 — `request()` is public
on every resource, so any endpoint is reachable in one line today:

```php
use Datomatic\ActiveCampaign\Enums\Method;

ActiveCampaign::contacts()->request(Method::GET, 'campaigns', responseKey: 'campaigns');
```

---

## 1 — Leftovers in what already ships

### 1.1 Correctness

Small, and they make what already ships behave better. Best value per hour.

| # | Item | Why it matters |
|---|---|---|
| 1 | **Rate limiting** | The API allows 5 requests/second per account. We retry a `429` but ignore its `Retry-After` header and sleep `retry_sleep` instead, and there is no client-side throttle — a loop over `updateListStatus()` or a batch of `untag()` calls can trip the limit on its own. |
| 2 | **`sync()` ignores `tags`** | The API accepts a `tags` array inside `contact` (additive, auto-creating). Supporting it removes a whole round-trip from the common "sync and tag" flow. |
| 3 | **`fields()->get()` discards sideloads** | `GET /fields/{id}` already returns `fieldOptions` and `fieldRels` next to `field`, and `responseCast()` drops them, so `options()` and `relations()` each cost an extra request. |
| 4 | **`automations()` advertises writes it cannot do** | The API documents no create/update/delete for automations. The methods are inherited from the base resource and will fail. Either override them to fail clearly, or split a read-only base class. |
| 5 | **Unverified inherited methods** | `PUT /lists`, and `update()`/`delete()` on `fieldOptions` and `fieldRels`, are not in the published reference and are untested against real responses. Confirm against a live account, then either document or remove. |
| 6 | **`createDeal()` does not validate the stage** | It does not check that the stage belongs to the pipeline. The API does, so this is only a nicer error. |
| 7 | **`relid: 0` is undocumented** | `fieldRels()->relate()` defaults to "all lists" via `ALL_LISTS = 0`, which is what the ActiveCampaign app sends but is not in the reference. Worth confirming. |

### 1.2 Pagination

| # | Item | Why it matters |
|---|---|---|
| 8 | **Descending cursor walks** | `lazy()` on contacts walks forward with `id_greater`. There is no `id_less` equivalent for walking newest-first. |
| 9 | **`cursorPaginate()`** | `paginate()` is offset-based everywhere, so deep page numbers on a large contact list stay slow. A cursor variant would fix that at the cost of addressable page numbers. |

### 1.3 Query builder

| # | Item | Why it matters |
|---|---|---|
| 10 | **No per-endpoint field awareness** | `Query` builds the syntax but does not know which filters each endpoint accepts, so a wrong field name is still only caught by the API. |

---

## 2 — Endpoint groups not wrapped at all

Ordered by how often they come up in real integrations.

| # | Group | Endpoints | Note |
|---|---|---|---|
| 11 | **Contact sub-resources** | `GET /contacts/{id}/fieldValues`, `/contactDeals`, `/organization` | cheap to add, they follow the `tags()` / `automations()` pattern already in the contacts resource |
| 12 | **Bulk contact edits** | `DELETE /contacts/bulk_delete`, `POST /contacts/bulk_edit` | completes the bulk story started by `import()` |
| 13 | **CRM custom fields** | `/dealCustomFieldMeta`, `/dealCustomFieldData`, `/accountCustomFieldMeta`, `/accountCustomFieldData`, and their bulk endpoints | deals and accounts are otherwise complete; this is the same gap contact fields had before 1.0 |
| 14 | **Deal tasks and activities** | `/dealTasks`, `/dealTasktypes`, `/dealActivities`, deal roles | |
| 15 | **Webhooks** | `/webhooks`, `/webhook/events`, `/webhook/listeners` | plus, on our side, signature verification and a way to route an event to a listener |
| 16 | **Campaigns** | `/campaigns`, `/campaigns/{id}/links`, `/messages` | |
| 17 | **Segments** | `/segments` | |
| 18 | **Forms** | `/forms` | |
| 19 | **Templates** | `/templates` | |
| 20 | **Users and groups** | `/users`, `/groups`, `/userGroups` | account administration |
| 21 | **Addresses** | `/addresses`, `/addressGroups`, `/addressLists` | required for compliance footers |
| 22 | **Scores** | `/scores` | contact and deal scoring rules |
| 23 | **List extras** | `/lists/{id}/contactGoalLists`, `/listGroups` | list group permissions |
| 24 | **Contact activity data** | `GET /contacts/{id}/geoIps`, `/trackingLogs`, `/scoreValues`, `/bounceLogs`, `/contactData` | read-only enrichment |
| 25 | **Rarely needed** | saved responses, calendar feeds, branding, task outcomes, configs, SMS, site messages | |

---

## 3 — Bigger design questions

These change the shape of the package, so they want a decision before code.

### 3.1 Events

Nothing is dispatched, so a consumer cannot hook into "contact synced" or "request failed" without
wrapping every call. Laravel's own `Illuminate\Http\Client\Events\ResponseReceived` fires but is not
scoped to this package.

### 3.2 DTOs / typed responses

Everything is a plain array: no typed properties, no IDE completion on results, and it is the reason
PHPStan stops at level 8 rather than 9 or 10. Arrays keep the wrapper thin and forward-compatible
with API changes, which is why 1.0 ships them. Worth revisiting only if the package grows well past
its current resource count.

### 3.3 Ecommerce Deep Data

`/ecomOrders`, `/ecomCustomers`, `/ecomOrderProducts`, `/ecomOrderActivities`, `/connections`.
A large surface and a separate concern — probably its own package,
`datomatic/laravel-active-campaign-ecommerce`, rather than more weight here.

### 3.4 Site and event tracking

`trackcmp.net/event`, `/whitelist`, `/status`. A **different host with different credentials**
(`actid` plus an event key), so it needs its own client and config block rather than a new resource.
Folding it into `active-campaign.php` would muddy a config that is currently one account's API
credentials.

---

## Suggested order

1. **Section 1.1, items 1–4** — rate limiting first; the rest are small correctness fixes.
2. **Items 11–13** — contact sub-resources, bulk edits, CRM custom fields. All follow patterns
   already in the codebase.
3. **Item 15, webhooks** — the highest-demand thing still entirely missing, and the piece with real
   design work on our side (signature verification).
4. **Items 16–25** — as demand appears. Each is a small resource on the existing base class.
5. **3.1 events**, then reassess 3.2, 3.3 and 3.4 as separate decisions.
