<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignAccountContactsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'accountContacts';

    protected ?string $responseKey = 'accountContacts';

    /**
     * Associate a contact with an account.
     *
     * @see https://developers.activecampaign.com/reference/create-an-association-1
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function associate(int $accountId, int $contactId, ?string $jobTitle = null): array
    {
        return $this->create(array_filter([
            'account' => $accountId,
            'contact' => $contactId,
            'jobTitle' => $jobTitle,
        ], fn (mixed $value): bool => ! is_null($value)));
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['accountContact' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['accountContact'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
