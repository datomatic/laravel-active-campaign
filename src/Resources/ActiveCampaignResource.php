<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignResourceContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

abstract class ActiveCampaignResource implements ActiveCampaignResourceContract
{
    protected string $resourceBasePath = '';

    public function __construct(
        private readonly ActiveCampaignClientContract $client,
    ) {}

    public function client(): ActiveCampaignClientContract
    {
        return $this->client;
    }

    /**
     * @throws RequestException
     * @throws ActiveCampaignException
     */
    public function request(Method $method, ?string $path = null, array $options = [], ?string $responseKey = null): array
    {
        /** @var Response $response */
        $response = $this->client()->send(
            method: $method,
            url: $path,
            data: $options
        );

        throw_if($response->failed(), ActiveCampaignException::requestError($path, $response->json()));

        return $response->json($responseKey);
    }

    /**
     * List all resources, search resources, or filter resources by query defined criteria.
     *
     * @return Collection<int, array>
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function list(?string $query = null, ?string $responseKey = null): Collection
    {
        $contacts = $this->request(
            method: Method::GET,
            path: $this->resourceBasePath.($query ? '?'.$query : ''),
            responseKey: $responseKey
        );

        return collect($contacts);
    }

    /**
     * Create a resource and get the contact id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function create(array $data): array
    {
        $contact = $this->request(
            method: Method::POST,
            path: $this->resourceBasePath,
            options: $this->requestCast($data)
        );

        return $this->responseCast($contact);
    }

    /**
     * Retreive an existing resource by their id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function get(int $id): array
    {
        $contact = $this->request(
            method: Method::GET,
            path: $this->resourceBasePath.'/'.$id,
        );

        return $this->responseCast($contact);
    }

    /**
     * Update an existing resource.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function update(int $id, array $data): array
    {
        $contact = $this->request(
            method: Method::PUT,
            path: $this->resourceBasePath.'/'.$id,
            options: $this->requestCast($data),
        );

        return $this->responseCast($contact);
    }

    /**
     * Delete an existing resource by their id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function delete(int $id): void
    {
        $this->request(
            method: Method::DELETE,
            path: $this->resourceBasePath.'/'.$id
        );
    }

    protected function responseCast(array $response): array
    {
        return $response;
    }

    protected function requestCast(array $request): array
    {
        return $request;
    }
}
