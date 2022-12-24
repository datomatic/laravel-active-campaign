<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Support\Collection;

class ActiveCampaignTagsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'tags';

    /**
     * Create a tag
     *
     * @throws ActiveCampaignException
     */
    public function createTag(string $name, string $description = ''): string
    {
        return parent::create([
            'tag' => $name,
            'description' => $description
        ]);
    }

    /**
     * Update an existing tag.
     *
     * @return array
     *
     * @throws ActiveCampaignException
     */
    public function updateTag(int $tagId, string $name, string $description = ''): array
    {
        return parent::update($tagId, [
            'tag' => $name,
            'description' => $description
        ]);
    }

    protected function requestCast(array $request): array
    {
        $request = ['tag' => $request];
        if (!isset($request['tag']['tagType'])) {
            $request['tag']['tagType'] = 'contact';
        }
        return $request;
    }

    protected function responseCast(array $response): array
    {
        $responseCast = $response['tag'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
