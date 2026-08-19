<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Concerns\SendsRequests;
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignResourceContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Support\Query;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

abstract class ActiveCampaignResource implements ActiveCampaignResourceContract
{
    use SendsRequests;

    /**
     * The API rejects anything above this, whatever we ask for.
     *
     * @see https://developers.activecampaign.com/reference/pagination
     */
    public const MAX_PER_PAGE = 100;

    public const DEFAULT_PER_PAGE = 20;

    protected string $resourceBasePath = '';

    protected ?string $responseKey = null;

    /**
     * List all resources, search resources, or filter resources by query defined criteria.
     *
     * Only the first page is returned: the API caps a page at 100 records and defaults to 20.
     * Use lazy(), all() or paginate() to go past that.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function list(Query|string|null $query = null, ?int $limit = null, ?int $offset = null): Collection
    {
        return collect($this->listPage($this->queryParams($query, array_filter([
            'limit' => $limit,
            'offset' => $offset,
        ], fn (?int $value): bool => ! is_null($value))))['records']);
    }

    /**
     * The total number of records matching the query, as reported by the API.
     *
     * Returns 0 for the few endpoints that do not send a meta.total back.
     *
     * @throws ActiveCampaignException
     */
    public function count(Query|string|null $query = null): int
    {
        return $this->listPage($this->queryParams($query, ['limit' => 1, 'offset' => 0]))['total'] ?? 0;
    }

    /**
     * A single page, wrapped in Laravel's paginator so it can be handed straight to a view.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function paginate(int $perPage = self::DEFAULT_PER_PAGE, ?int $page = null, Query|string|null $query = null): LengthAwarePaginator
    {
        $perPage = $this->clampPerPage($perPage);
        $page = max($page ?? Paginator::resolveCurrentPage(), 1);

        $result = $this->listPage($this->queryParams($query, [
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]));

        return new LengthAwarePaginator(
            items: $result['records'],
            total: $result['total'] ?? count($result['records']),
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => Paginator::resolveCurrentPath()],
        );
    }

    /**
     * Walk every page, fetching one at a time.
     *
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function lazy(Query|string|null $query = null, int $perPage = self::MAX_PER_PAGE): LazyCollection
    {
        $perPage = $this->clampPerPage($perPage);

        return LazyCollection::make(function () use ($query, $perPage) {
            $offset = 0;

            do {
                $records = $this->listPage($this->queryParams($query, [
                    'limit' => $perPage,
                    'offset' => $offset,
                ]))['records'];

                foreach ($records as $record) {
                    yield $record;
                }

                $offset += $perPage;
            } while (count($records) === $perPage);
        });
    }

    /**
     * Every record matching the query, in memory. Prefer lazy() on large collections.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function all(Query|string|null $query = null, int $perPage = self::MAX_PER_PAGE): Collection
    {
        return $this->lazy($query, $perPage)->collect();
    }

    /**
     * Create a resource.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function create(array $data): array
    {
        $obj = $this->request(
            method: Method::POST,
            path: $this->resourceBasePath,
            options: $this->requestCast($data)
        );

        return $this->responseCast($obj);
    }

    /**
     * Retrieve an existing resource by its id.
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function get(int $id): array
    {
        $obj = $this->request(
            method: Method::GET,
            path: $this->resourceBasePath.'/'.$id,
        );

        return $this->responseCast($obj);
    }

    /**
     * Update an existing resource.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function update(int $id, array $data): array
    {
        $obj = $this->request(
            method: Method::PUT,
            path: $this->resourceBasePath.'/'.$id,
            options: $this->requestCast($data),
        );

        return $this->responseCast($obj);
    }

    /**
     * Delete an existing resource by its id.
     *
     * @throws ActiveCampaignException
     */
    public function delete(int $id): void
    {
        $this->request(
            method: Method::DELETE,
            path: $this->resourceBasePath.'/'.$id
        );
    }

    /**
     * Fetch one page and split it into its records and the total the API reports.
     *
     * @param  array<array-key, mixed>  $params
     * @return array{records: array<int, array<string, mixed>>, total: int|null}
     *
     * @throws ActiveCampaignException
     */
    protected function listPage(array $params): array
    {
        $body = $this->request(
            method: Method::GET,
            path: $this->resourceBasePath.($params === [] ? '' : '?'.http_build_query($params)),
        );

        return [
            'records' => is_null($this->responseKey) ? $body : ($body[$this->responseKey] ?? []),
            'total' => isset($body['meta']['total']) ? intval($body['meta']['total']) : null,
        ];
    }

    /**
     * Merge the caller's query with the params we control.
     * Ours win on a collision, so pagination stays reliable.
     *
     * @param  array<array-key, mixed>  $overrides
     * @return array<array-key, mixed>
     */
    protected function queryParams(Query|string|null $query, array $overrides = []): array
    {
        $params = [];

        if ($query instanceof Query) {
            $params = $query->toArray();
        } elseif (! is_null($query) && $query !== '') {
            parse_str(ltrim($query, '?'), $params);
        }

        return array_replace($params, $overrides);
    }

    protected function clampPerPage(int $perPage): int
    {
        return min(max($perPage, 1), self::MAX_PER_PAGE);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        return $response;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return $request;
    }
}
