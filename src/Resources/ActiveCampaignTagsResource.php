<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\ActiveCampaignResource;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Support\Collection;

class ActiveCampaignTagsResource extends ActiveCampaignResource
{
    /**
     * Retreive an existing tag by their id.
     *
     * @throws ActiveCampaignException|\Illuminate\Http\Client\RequestException
     */
    public function get(int $id): array
    {
        $tag = $this->request(
            method: Method::GET,
            path: 'tags/'.$id,
            responseKey: 'tag'
        );

        return $this->responseArray($tag);
    }

    /**
     * List all tags filtered by name
     *
     * @return Collection<int, array>
     *
     * @throws ActiveCampaignException|\Illuminate\Http\Client\RequestException
     */
    public function list(?string $name = ''): Collection
    {
        $tags = $this->request(
            method: Method::GET,
            path: 'tags?search='.$name,
            responseKey: 'tags'
        );

        return collect($tags);
    }

    /**
     * Create a tag
     *
     * @throws ActiveCampaignException
     */
    public function create(string $name, string $description = ''): string
    {
        $tag = $this->request(
            method: Method::POST,
            path: 'tags',
            options: [
                'tag' => [
                    'tag' => $name,
                    'description' => $description,
                    'tagType' => 'contact',
                ],
            ],
            responseKey: 'tag'
        );

        return $this->responseArray($tag);
    }

    /**
     * Update an existing tag.
     *
     * @return array
     *
     * @throws ActiveCampaignException
     */
    public function update(int $tagId, string $name, string $description = ''): array
    {
        $tag = $this->request(
            method: Method::PUT,
            path: 'tags/'.$tagId,
            options: [
                'tag' => [
                    'tag' => $name,
                    'description' => $description,
                    'tagType' => 'contact',
                ],
            ],
            responseKey: 'tag'
        );

        return $this->responseArray($tag);
    }

    /**
     * Delete an existing tag by their id.
     *
     * @param  int  $id
     * @return void
     *
     * @throws ActiveCampaignException
     */
    public function delete(int $id): void
    {
        $this->request(
            method: Method::DELETE,
            path: 'tags/'.$id
        );
    }

    protected function responseArray(array $response)
    {
        $responseArray = $response['contact'];

        unset($responseArray['links']);

        return $responseArray;
    }
}
