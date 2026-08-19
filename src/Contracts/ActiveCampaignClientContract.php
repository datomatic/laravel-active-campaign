<?php

namespace Datomatic\ActiveCampaign\Contracts;

use Datomatic\ActiveCampaign\Enums\Method;
use Illuminate\Http\Client\Response;

interface ActiveCampaignClientContract
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(Method $method, string $url, array $data = []): Response;
}
