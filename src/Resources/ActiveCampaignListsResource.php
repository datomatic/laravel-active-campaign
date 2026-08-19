<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignListsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'lists';

    protected ?string $responseKey = 'lists';

    /**
     * Create a list. The four arguments are the fields the API requires.
     *
     * @see https://developers.activecampaign.com/reference/create-new-list
     *
     * @param  string  $stringId  URL-safe name, e.g. "monthly-newsletter"
     * @param  string  $senderUrl  the website this list is for
     * @param  string  $senderReminder  why the contact is receiving mail from this list
     * @param  array<string, mixed>  $attributes  any other supported attribute (channel, user, carboncopy, ...)
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createList(
        string $name,
        string $stringId,
        string $senderUrl,
        string $senderReminder,
        array $attributes = [],
    ): array {
        return $this->create([
            'name' => $name,
            'stringid' => $stringId,
            'sender_url' => $senderUrl,
            'sender_reminder' => $senderReminder,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['list' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['list'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
