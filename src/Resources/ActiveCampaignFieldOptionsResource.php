<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Support\Collection;

class ActiveCampaignFieldOptionsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'fieldOptions';

    protected ?string $responseKey = 'fieldOptions';

    /**
     * Create the selectable options of a dropdown, listbox, radio, checkbox or multiselect field.
     *
     * @see https://developers.activecampaign.com/reference/create-custom-field-options
     *
     * @param  array<int, array<string, mixed>>  $options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function createMany(array $options): Collection
    {
        $created = $this->request(
            method: Method::POST,
            path: 'fieldOption/bulk',
            options: ['fieldOptions' => array_values($options)],
            responseKey: 'fieldOptions',
        );

        return collect($created);
    }

    /**
     * The API only documents bulk creation, so a single option goes out as a one-element bulk call.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function create(array $data): array
    {
        return $this->createMany([$data])->first() ?? [];
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['fieldOption' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['fieldOption'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
