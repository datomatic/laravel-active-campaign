<?php

namespace Datomatic\ActiveCampaign;

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignResourceContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

abstract class ActiveCampaignResource implements ActiveCampaignResourceContract
{
    public function __construct(
        private readonly ActiveCampaignClientContract $client,
    ) {
    }

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
            method: $method->value,
            url: $path,
            options: $options
        );

        throw_if($response->failed(), ActiveCampaignException::requestError($path, $response->json()));

        return $response->json($responseKey);
    }
}
