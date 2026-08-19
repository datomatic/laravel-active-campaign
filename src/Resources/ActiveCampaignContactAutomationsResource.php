<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignContactAutomationsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'contactAutomations';

    protected ?string $responseKey = 'contactAutomations';

    /**
     * Start an automation for a contact.
     *
     * @see https://developers.activecampaign.com/reference/create-new-contactautomation
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function add(int $contactId, int $automationId): array
    {
        return $this->create([
            'contact' => $contactId,
            'automation' => $automationId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['contactAutomation' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['contactAutomation'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
