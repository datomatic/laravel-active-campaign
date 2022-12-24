<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Http\Client\RequestException;

class ActiveCampaignFieldResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'fields';

    /**
     * Create a field value type safe.
     *
     * @throws ActiveCampaignException
     * @throws RequestException
     */
    public function createField(int $field, string $value): array
    {
        return parent::create([
            'field' => $field,
            'value' => $value,
        ]);
    }

    /**
     * Update an existing field value type safe.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function updateFieldValue(int $id, int $field, string $value): array
    {
        return parent::update($id, [
            'field' => $field,
            'value' => $value,
        ]);
    }

    protected function requestCast(array $request): array
    {
        return [ 'field' => $request];
    }

    protected function responseCast(array $response): array
    {
        $responseCast = $response['field'];

        return $responseCast;
    }
}
