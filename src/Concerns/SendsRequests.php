<?php

namespace Datomatic\ActiveCampaign\Concerns;

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

trait SendsRequests
{
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
}
