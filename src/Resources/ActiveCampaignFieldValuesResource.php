<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignFieldValuesResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'fieldValues';

    protected ?string $responseKey = 'fieldValues';

    /**
     * Set the value of a custom field for a contact.
     *
     * @see https://developers.activecampaign.com/reference/create-fieldvalue
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createFieldValue(int $contactId, int $fieldId, string $value): array
    {
        return $this->create([
            'contact' => $contactId,
            'field' => $fieldId,
            'value' => $value,
        ]);
    }

    /**
     * Update an existing field value.
     *
     * @see https://developers.activecampaign.com/reference/update-a-fieldvalue
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function updateFieldValue(int $id, int $fieldId, string $value): array
    {
        return $this->update($id, [
            'field' => $fieldId,
            'value' => $value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['fieldValue' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['fieldValue'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
