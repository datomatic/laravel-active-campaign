<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignResourceContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Support\Collection;

abstract class ActiveCampaignResource implements ActiveCampaignResourceContract
{
    protected string $resourceBasePath = '';

    protected ?string $responseKey = null;

    public function __construct(
        private readonly ActiveCampaignClientContract $client,
    ) {}

    public function client(): ActiveCampaignClientContract
    {
        return $this->client;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<mixed>
     *
     * @throws ActiveCampaignException
     */
    public function request(Method $method, string $path = '', array $options = [], ?string $responseKey = null): array
    {
        $response = $this->client()->send(
            method: $method,
            url: $path,
            data: $options
        );

        if ($response->failed()) {
            throw ActiveCampaignException::requestError($path, $response->json());
        }

        return $response->json($responseKey) ?? [];
    }

    /**
     * List all resources, search resources, or filter resources by query defined criteria.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function list(?string $query = null): Collection
    {
        $objs = $this->request(
            method: Method::GET,
            path: $this->resourceBasePath.($query ? '?'.$query : ''),
            responseKey: $this->responseKey
        );

        return collect($objs);
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
