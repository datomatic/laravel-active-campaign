<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

/**
 * Pipelines are called dealGroups in the API.
 */
class ActiveCampaignPipelinesResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'dealGroups';

    protected ?string $responseKey = 'dealGroups';

    /**
     * Create a pipeline. The API also creates three default stages inside it:
     * "To Contact", "In Contact" and "Follow Up".
     *
     * @see https://developers.activecampaign.com/reference/create-a-pipeline
     *
     * @param  array<string, mixed>  $attributes  any other supported attribute (currency, autoassign, users, groups, ...)
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createPipeline(string $title, array $attributes = []): array
    {
        return $this->create([
            'title' => $title,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['dealGroup' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['dealGroup'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
