<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Http\Client\RequestException;

class ActiveCampaignFieldValuesResource extends ActiveCampaignResource
{
    /**
     * Retreive an existing field value by their id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function get(int $id): array
    {
        $fieldValue = $this->request(
            method: Method::GET,
            path: 'fieldValues/'.$id,
            responseKey: 'fieldValue'
        );

        return $this->responseArray($fieldValue);
    }

    /**
     * Create a field value and get the id.
     *
     * @return array
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function create(int $contactId, int $field, string $value): array
    {
        $fieldValue = $this->request(
            method: Method::POST,
            path: 'fieldValues',
            options: [
                'fieldValue' => [
                    'id' => $contactId,
                    'field' => $field,
                    'value' => $value,
                ],
            ],
            responseKey: 'contact'
        );

        return $this->responseArray($fieldValue);
    }

    /**
     * Update an existing field value.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function update(int $contactId, int $field, string $value): array
    {
        $fieldValue = $this->request(
            method: Method::PUT,
            path: 'fieldValues/'.$contactId,
            options: [
                'fieldValue' => [
                    'id' => $contactId,
                    'field' => $field,
                    'value' => $value,
                ],
            ],
            responseKey: 'fieldValue'
        );

        return $this->responseArray($fieldValue);
    }

    /**
     * Delete an existing field value by their id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function delete(int $fieldValueId): void
    {
        $this->request(
            method: Method::DELETE,
            path: 'fieldValues/'.$fieldValueId
        );
    }

    protected function responseArray(array $response)
    {
        $responseArray = $response['contact'];

        unset($responseArray['links']);

        return $responseArray;
    }
}
