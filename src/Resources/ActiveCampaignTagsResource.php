<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

class ActiveCampaignTagsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'tags';

    /**
     * @return Collection<int, array>
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function list(?string $query = null, ?string $responseKey = null): Collection
    {
        return parent::list($query, 'tags');
    }

    /**
     * Create a tag
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function createTag(string $name, string $description = ''): array
    {
        return parent::create([
            'tag' => $name,
            'description' => $description,
        ]);
    }

    /**
     * Update an existing tag.
     *
     * @return array
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function updateTag(int $tagId, string $name, string $description = ''): array
    {
        return parent::update($tagId, [
            'tag' => $name,
            'description' => $description,
        ]);
    }

    protected function requestCast(array $request): array
    {
        $request = ['tag' => $request];
        if (! isset($request['tag']['tagType'])) {
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
