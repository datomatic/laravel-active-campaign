<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignFieldRelsResource extends ActiveCampaignResource
{
    /**
     * ActiveCampaign uses list id 0 to relate a field to every list. The value is what the app
     * itself sends, but it is not part of the published reference, so pass real list ids when
     * you know them.
     */
    public const ALL_LISTS = 0;

    protected string $resourceBasePath = 'fieldRels';

    protected ?string $responseKey = 'fieldRels';

    /**
     * Relate a custom field to a list. A field is invisible on a contact until one of the lists
     * the contact belongs to is related to it.
     *
     * @see https://developers.activecampaign.com/reference/create-a-custom-field-relationship-to-lists
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function relate(int $fieldId, int $listId = self::ALL_LISTS): array
    {
        return $this->create([
            'field' => $fieldId,
            'relid' => $listId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['fieldRel' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['fieldRel'];

        unset($responseCast['links']);

        return $responseCast;
    }
}
