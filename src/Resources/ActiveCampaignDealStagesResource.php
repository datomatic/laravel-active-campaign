<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignDealStagesResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'dealStages';

    protected ?string $responseKey = 'dealStages';

    /**
     * Create a stage inside a pipeline.
     *
     * @see https://developers.activecampaign.com/reference/create-a-deal-stage
     *
     * @param  int  $groupId  the pipeline the stage belongs to
     * @param  array<string, mixed>  $attributes  any other supported attribute (order, color, width, cardRegion1, ...)
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createStage(string $title, int $groupId, array $attributes = []): array
    {
        return $this->create([
            'title' => $title,
            'group' => $groupId,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['dealStage' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['dealStage'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
