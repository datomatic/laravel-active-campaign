<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\FieldType;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignFieldsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'fields';

    protected ?string $responseKey = 'fields';

    /**
     * Create a custom field.
     *
     * @see https://developers.activecampaign.com/reference/create-a-new-field
     *
     * @param  array<string, mixed>  $attributes  any other supported attribute (descript, perstag, defval, visible, ordernum, ...)
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createField(string $title, FieldType $type = FieldType::Text, array $attributes = []): array
    {
        return $this->create([
            'title' => $title,
            'type' => $type->value,
            ...$attributes,
        ]);
    }

    /**
     * Update an existing custom field.
     *
     * @see https://developers.activecampaign.com/reference/update-a-custom-field
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function updateField(int $id, string $title, FieldType $type = FieldType::Text, array $attributes = []): array
    {
        return $this->update($id, [
            'title' => $title,
            'type' => $type->value,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['field' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['field'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
