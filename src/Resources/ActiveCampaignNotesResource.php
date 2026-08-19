<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\NoteRelType;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;

class ActiveCampaignNotesResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'notes';

    protected ?string $responseKey = 'notes';

    /**
     * Attach a note to a contact, deal, account, task or activity.
     *
     * @see https://developers.activecampaign.com/reference/create-a-note
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function createNote(string $note, int $relId, NoteRelType $relType = NoteRelType::Contact): array
    {
        return $this->create([
            'note' => $note,
            'relid' => $relId,
            'reltype' => $relType->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    protected function requestCast(array $request): array
    {
        return ['note' => $request];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['note'] ?? [];

        unset($responseCast['links']);

        return $responseCast;
    }
}
