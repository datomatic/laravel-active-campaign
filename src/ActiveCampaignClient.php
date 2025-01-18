<?php

namespace Datomatic\ActiveCampaign;

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class ActiveCampaignClient implements ActiveCampaignClientContract
{
    public function __construct(
        private readonly PendingRequest $request,
    ) {}

    public function send(Method $method, string $url, array $data = []): Response
    {
        return $this->request->throw()->send(
            method: $method->value,
            url: $url,
            options: ['json' => $data],
        );
    }
}
