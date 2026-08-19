<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignAccountsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'accounts';

    protected ?string $responseKey = 'accounts';

    /**
     * Create an account. Only the name is required.
     *
     * @see https://developers.activecampaign.com/reference/create-an-account-new
     *
     * @param  array<string, mixed>  $attributes  any other supported attribute (accountUrl, owner, fields, ...)
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createAccount(string $name, array $attributes = []): array
    {
        return $this->create([
            'name' => $name,
            ...$attributes,
        ]);
    }

    /**
     * Associate a contact with this account, optionally with their job title.
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function addContact(int $accountId, int $contactId, ?string $jobTitle = null): array
    {
        return (new ActiveCampaignAccountContactsResource($this->client()))
            ->associate($accountId, $contactId, $jobTitle);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['account' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['account'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
