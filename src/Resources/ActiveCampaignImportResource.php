<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Concerns\SendsRequests;
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignResourceContract;
use Datomatic\ActiveCampaign\Enums\BulkImportStatus;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * The bulk contact importer. Queues contacts instead of writing them one at a time, which is the
 * only realistic way to move more than a few hundred contacts against a 5 request/second limit.
 *
 * This endpoint group has no CRUD surface, so the resource deliberately offers none.
 *
 * @see https://developers.activecampaign.com/reference/bulk-import-contacts
 */
class ActiveCampaignImportResource implements ActiveCampaignResourceContract
{
    use SendsRequests;

    /**
     * The API rejects a larger batch.
     */
    public const MAX_BATCH_SIZE = 250;

    /**
     * Queue one batch of contacts.
     *
     * Contacts are accepted in the same shape as contacts()->sync() — `firstName`, `lastName` and
     * the custom field names from the config file — and translated to the different shape this
     * endpoint expects. The endpoint's own keys (`first_name`, `fields`, …) are passed through
     * untouched if you prefer to write them directly.
     *
     * @param  array<int, array<string, mixed>>  $contacts  at most MAX_BATCH_SIZE
     * @param  array<string, mixed>|null  $callback  a webhook the API calls when the batch finishes
     * @return array{Success?: int, queued_contacts?: int, batchId?: string, message?: string}
     *
     * @throws ActiveCampaignException
     */
    public function bulk(array $contacts, ?array $callback = null): array
    {
        throw_if(
            count($contacts) > self::MAX_BATCH_SIZE,
            ActiveCampaignException::batchTooLarge(count($contacts), self::MAX_BATCH_SIZE)
        );

        $payload = ['contacts' => array_values(array_map($this->castContact(...), $contacts))];

        if (! is_null($callback)) {
            $payload['callback'] = $callback;
        }

        /** @var array{Success?: int, queued_contacts?: int, batchId?: string, message?: string} $result */
        $result = $this->request(
            method: Method::POST,
            path: 'import/bulk_import',
            options: $payload,
        );

        return $result;
    }

    /**
     * Queue any number of contacts, split into batches the API will accept.
     *
     * @param  iterable<int, array<string, mixed>>  $contacts
     * @param  array<string, mixed>|null  $callback
     * @return Collection<int, array<string, mixed>> one entry per batch, in order
     *
     * @throws ActiveCampaignException
     */
    public function bulkAll(iterable $contacts, ?array $callback = null, int $batchSize = self::MAX_BATCH_SIZE): Collection
    {
        $batchSize = min(max($batchSize, 1), self::MAX_BATCH_SIZE);

        return LazyCollection::make(function () use ($contacts) {
            yield from $contacts;
        })
            ->chunk($batchSize)
            ->map(fn (LazyCollection $batch): array => $this->bulk($batch->values()->all(), $callback))
            ->collect();
    }

    /**
     * The outcome of one batch: which contacts were created and which emails were rejected.
     *
     * Called immediately after queueing, `status` may come back as `false` because the API has not
     * set one yet, so leave a short delay before polling.
     *
     * @see https://developers.activecampaign.com/reference/bulk-import-status-info
     *
     * @return array{status?: string|bool, success?: array<int, string>, failure?: array<int, string>}
     *
     * @throws ActiveCampaignException
     */
    public function status(string $batchId): array
    {
        /** @var array{status?: string|bool, success?: array<int, string>, failure?: array<int, string>} $result */
        $result = $this->request(
            method: Method::GET,
            path: 'import/info?'.http_build_query(['batchId' => $batchId]),
        );

        return $result;
    }

    /**
     * The status of a batch as an enum, or null while the API has not set one yet.
     *
     * @throws ActiveCampaignException
     */
    public function statusOf(string $batchId): ?BulkImportStatus
    {
        $status = $this->status($batchId)['status'] ?? null;

        return is_string($status) ? BulkImportStatus::tryFrom($status) : null;
    }

    /**
     * How much import work is outstanding and what completed recently, account wide.
     *
     * @return array{outstanding?: array<int, array<string, mixed>>, recentlyCompleted?: array<int, array<string, mixed>>}
     *
     * @throws ActiveCampaignException
     */
    public function info(): array
    {
        /** @var array{outstanding?: array<int, array<string, mixed>>, recentlyCompleted?: array<int, array<string, mixed>>} $result */
        $result = $this->request(
            method: Method::GET,
            path: 'import/bulk_import',
        );

        return $result;
    }

    /**
     * The importer names things differently from the rest of the API, so accept both spellings.
     *
     * @param  array<string, mixed>  $contact
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    protected function castContact(array $contact): array
    {
        throw_if(empty($contact['email']), ActiveCampaignException::missingField('import/bulk_import', 'email'));

        $cast = collect($contact)
            ->only(['email', 'first_name', 'last_name', 'phone', 'customer_acct_name', 'tags', 'fields'])
            ->all();

        foreach (['firstName' => 'first_name', 'lastName' => 'last_name'] as $ours => $theirs) {
            if (isset($contact[$ours]) && ! isset($cast[$theirs])) {
                $cast[$theirs] = $contact[$ours];
            }
        }

        $fields = collect(ActiveCampaignConfig::customFields())
            ->filter(fn ($customFieldId, $customFieldName) => ! empty($contact[$customFieldName]))
            ->map(fn ($customFieldId, $customFieldName) => [
                'id' => intval($customFieldId),
                'value' => $contact[$customFieldName],
            ])->values()->all();

        if ($fields !== []) {
            $cast['fields'] = [...($cast['fields'] ?? []), ...$fields];
        }

        foreach (['subscribe', 'unsubscribe'] as $key) {
            if (isset($contact[$key])) {
                $cast[$key] = $this->castLists($contact[$key]);
            }
        }

        return $cast;
    }

    /**
     * Accept a plain list of ids as well as the [{"listid": 1}] shape the endpoint wants.
     *
     * @param  array<int, int|array<string, mixed>>  $lists
     * @return array<int, array<string, mixed>>
     */
    protected function castLists(array $lists): array
    {
        return array_values(array_map(
            fn (int|array $list): array => is_array($list) ? $list : ['listid' => $list],
            $lists
        ));
    }
}
