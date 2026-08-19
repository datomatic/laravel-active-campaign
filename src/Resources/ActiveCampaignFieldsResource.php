<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\FieldType;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Support\Collection;

class ActiveCampaignFieldsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'fields';

    protected ?string $responseKey = 'fields';

    /**
     * Create a custom field.
     *
     * A field is not usable until it is related to a list, and a field whose type has selectable
     * values is not usable until its options exist, so both can be created in the same call.
     *
     * @see https://developers.activecampaign.com/reference/contact-custom-fields-api-guide
     *
     * @param  array<string, mixed>  $attributes  any other supported attribute (descript, perstag, defval, visible, ordernum, ...)
     * @param  array<int, string|array<string, mixed>>  $options  selectable values, as plain strings or full option arrays
     * @param  array<int, int>  $lists  list ids to relate the field to
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createField(
        string $title,
        FieldType $type = FieldType::Text,
        array $attributes = [],
        array $options = [],
        array $lists = [],
    ): array {
        $field = $this->create([
            'title' => $title,
            'type' => $type->value,
            ...$attributes,
        ]);

        $fieldId = intval($field['id']);

        if ($options !== []) {
            $this->createOptions($fieldId, $options);
        }

        foreach ($lists as $listId) {
            $this->relate($fieldId, $listId);
        }

        return $field;
    }

    /**
     * Update an existing custom field.
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
     * Add selectable values to a field. Plain strings become an option whose label and value match.
     *
     * @param  array<int, string|array<string, mixed>>  $options
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function createOptions(int $fieldId, array $options): Collection
    {
        return $this->fieldOptions()->createMany(
            collect($options)->values()->map(function (string|array $option, int $index) use ($fieldId): array {
                $option = is_array($option) ? $option : ['value' => $option];
                $option['label'] ??= $option['value'];

                return [
                    'field' => $fieldId,
                    'orderid' => $index + 1,
                    ...$option,
                ];
            })->all()
        );
    }

    /**
     * The selectable values of a field.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function options(int $fieldId): Collection
    {
        return collect($this->request(
            method: Method::GET,
            path: 'fields/'.$fieldId.'/options',
            responseKey: 'fieldOptions',
        ));
    }

    /**
     * Relate the field to a list, without which it stays invisible on a contact.
     *
     * Passing no list id relates it to every list.
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function relate(int $fieldId, int $listId = ActiveCampaignFieldRelsResource::ALL_LISTS): array
    {
        return $this->fieldRels()->relate($fieldId, $listId);
    }

    /**
     * The lists a field is related to.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function relations(int $fieldId): Collection
    {
        return collect($this->request(
            method: Method::GET,
            path: 'fields/'.$fieldId.'/relations',
            responseKey: 'fieldRels',
        ));
    }

    protected function fieldOptions(): ActiveCampaignFieldOptionsResource
    {
        return new ActiveCampaignFieldOptionsResource($this->client());
    }

    protected function fieldRels(): ActiveCampaignFieldRelsResource
    {
        return new ActiveCampaignFieldRelsResource($this->client());
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
        $responseCast = $response['field'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
