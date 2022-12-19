<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\ActiveCampaignResource;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

class ActiveCampaignContactsResource extends ActiveCampaignResource
{
    /**
     * Retreive an existing contact by their id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function get(int $contactId): array
    {
        $contact = $this->request(
            method: Method::GET,
            path: 'contacts/'.$contactId,
        );

        return $this->responseArray($contact);
    }

    /**
     * List all contact, search contacts, or filter contacts by query defined criteria.
     *
     * @return Collection<int, array>
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function list(string $query = ''): Collection
    {
        $contacts = $this->request(
            method: Method::GET,
            path: 'contacts?'.$query,
            responseKey: 'contacts'
        );

        return collect($contacts);
    }

    /**
     * Create a contact and get the contact id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function create(array $contactArray): string
    {
        $contact = $this->request(
            method: Method::POST,
            path: 'contacts',
            options: [
                'contact' => $this->requestArray($contactArray),
            ]
        );

        return $this->responseArray($contact);
    }

    /**
     * Sync an existing contact without passing id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function sync(array $contactArray): array
    {
        $contact = $this->request(
            method: Method::POST,
            path: 'contacts/sync',
            options: [
                'contact' => $this->requestArray($contactArray),
            ],
        );

        return $this->responseArray($contact);
    }

    /**
     * Update an existing contact.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function update(int $contactId, array $contactArray): array
    {
        $contact = $this->request(
            method: Method::PUT,
            path: 'contacts/'.$contactId,
            options: [
                'contact' => $this->requestArray($contactArray),
            ],
        );

        return $this->responseArray($contact);
    }

    /**
     * Delete an existing contact by their id.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function delete(int $contactId): void
    {
        $this->request(
            method: Method::DELETE,
            path: 'contacts/'.$contactId
        );
    }

    /**
     * Get a contactTags list of a contact.
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function tags(int $contactId): array
    {
        $contactTags = $this->request(
            method: Method::GET,
            path: 'contacts/'.$contactId.'/contactTags',
            responseKey: 'contactTags'
        );

        return $contactTags;
    }

    /**
     * Add a tag to a contact.
     *
     * @see https://developers.activecampaign.com/reference/create-contact-tag
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function tag(int $contactId, int $tagId): array
    {
        $contactTag = $this->request(
            method: Method::POST,
            path: 'contactTags',
            options: [
                'contactTag' => [
                    'contact' => $contactId,
                    'tag' => $tagId,
                ],
            ],
            responseKey: 'contactTag'
        );

        return $contactTag;
    }

    /**
     * Remove a tag from a contact.
     *
     * @see https://developers.activecampaign.com/reference#delete-contact-tag
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function untag(int $contactId, int $tagId): void
    {
        $contactTagId = $this->getContactTagId($contactId, $tagId);

        if ($contactTagId) {
            $this->request(
                method: Method::DELETE,
                path: 'contactTags/'.$contactTagId
            );
        } else {
            ActiveCampaignException::contactTagMissing($contactId, $tagId);
        }
    }

    /**
     * Remove a tag from a contact without exceptions.
     *
     * @see https://developers.activecampaign.com/reference#delete-contact-tag
     */
    public function tryUntag(int $contactId, int $tagId): void
    {
        try {
            $this->untag($contactId, $tagId);
        } catch (ActiveCampaignException) {
        }
    }

    /**
     * @param  array  $contactArray
     * @return mixed[]
     */
    protected function requestArray(array $contactArray): array
    {
        throw_if(empty($contactArray['email']), ActiveCampaignException::missingField('contacts', 'email'));

        $array = collect($contactArray)->only(['email', 'firstName', 'lastName', 'phone'])->toArray();
        $array['fieldValues'] = collect(ActiveCampaignConfig::customFields())
            ->filter(fn ($customFieldId, $customFieldName) => ! empty($contactArray[$customFieldName]))
            ->map(fn ($customFieldId, $customFieldName) => [
                'field' => strval($customFieldId),
                'value' => $contactArray[$customFieldName],
            ])->all();

        return $array;
    }

    protected function responseArray(array $response)
    {
        $responseArray = $response['contact'];

        unset($responseArray['links']);

        $customFields = ActiveCampaignConfig::customFields();
        if (! empty($customFields)) {
            $customFieldNames = array_flip($customFields);
            foreach ($response['fieldValues'] as $customField) {
                $customFieldId = intval($customField['field']);
                if (in_array($customFieldId, $customFields)) {
                    $responseArray[$customFieldNames[$customFieldId]] = $customField['value'];
                }
            }
        }

        return $responseArray;
    }

    /**
     * Get contactTag id if associated to contact
     *
     * @throws ActiveCampaignException|RequestException
     */
    public function getContactTagId(int $contactId, int $tagId): ?int
    {
        $contactTags = $this->tags($contactId);

        foreach ($contactTags as $contactTag) {
            if (intval($contactTag['tag_id']) === $tagId) {
                return intval($contactTag['id']);
            }
        }

        return null;
    }
}
