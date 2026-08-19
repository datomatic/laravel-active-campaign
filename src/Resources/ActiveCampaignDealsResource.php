<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignDealsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'deals';

    protected ?string $responseKey = 'deals';

    /**
     * Create a deal.
     *
     * @see https://developers.activecampaign.com/reference/create-a-deal-new
     *
     * @param  int  $value  in cents
     * @param  string  $currency  3-letter ISO code, lowercase
     * @param  int  $groupId  the pipeline
     * @param  array<string, mixed>  $attributes  any other supported attribute (status, percent, description, ...)
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createDeal(
        string $title,
        int $value,
        string $currency,
        int $groupId,
        int $stageId,
        int $ownerId,
        ?int $contactId = null,
        ?int $accountId = null,
        array $attributes = [],
    ): array {
        throw_if(
            is_null($contactId) && is_null($accountId),
            ActiveCampaignException::missingOneOf('deals', ['contact', 'account'])
        );

        return $this->create(array_filter([
            'title' => $title,
            'value' => $value,
            'currency' => strtolower($currency),
            'group' => $groupId,
            'stage' => $stageId,
            'owner' => $ownerId,
            'contact' => $contactId,
            'account' => $accountId,
            ...$attributes,
        ], fn (mixed $value): bool => ! is_null($value)));
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['deal' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['deal'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
