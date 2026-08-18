<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\TagType;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignTagsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'tags';

    protected ?string $responseKey = 'tags';

    /**
     * Create a tag.
     *
     * @see https://developers.activecampaign.com/reference/create-a-new-tag
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createTag(string $name, string $description = '', TagType $type = TagType::Contact): array
    {
        return $this->create([
            'tag' => $name,
            'description' => $description,
            'tagType' => $type->value,
        ]);
    }

    /**
     * Update an existing tag.
     *
     * @see https://developers.activecampaign.com/reference/update-a-tag
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function updateTag(int $tagId, string $name, string $description = '', TagType $type = TagType::Contact): array
    {
        return $this->update($tagId, [
            'tag' => $name,
            'description' => $description,
            'tagType' => $type->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        $request['tagType'] ??= TagType::Contact->value;

        return ['tag' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['tag'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
