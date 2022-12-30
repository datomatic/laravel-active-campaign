<?php

namespace Datomatic\ActiveCampaign\Contracts;

use Datomatic\ActiveCampaign\Enums\Method;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * @property-read PendingRequest $request
 */
interface ActiveCampaignClientContract
{
    public function send(Method $method, string $url, array $data = []): Response;
}
