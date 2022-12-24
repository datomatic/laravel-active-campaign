<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

class ActiveCampaignFieldsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'fields';

    /**
     * @return Collection<int, array>
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function list(?string $query = null, ?string $responseKey = null): Collection
    {
        return parent::list($query, 'fields');
    }

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
        return ['field' => $request];
    }

    protected function responseCast(array $response): array
    {
        $responseCast = $response['field'];

        return $responseCast;
    }
}
