<?php

namespace Datomatic\ActiveCampaign\Resources;

class ActiveCampaignAutomationsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'automations';

    protected ?string $responseKey = 'automations';

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['automation'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
