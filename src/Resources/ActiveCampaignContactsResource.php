<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\ListStatus;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;

class ActiveCampaignContactsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'contacts';

    protected ?string $responseKey = 'contacts';

    /**
     * Create the contact if the email is unknown, update it otherwise.
     *
     * @see https://developers.activecampaign.com/reference/sync-a-contacts-data
     *
     * @param  array<string, mixed>  $contactArray
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function sync(array $contactArray): array
    {
        $contact = $this->request(
            method: Method::POST,
            path: 'contact/sync',
            options: $this->requestCast($contactArray),
        );

        return $this->responseCast($contact);
    }

    /**
     * Get a contactTags list of a contact.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws ActiveCampaignException
     */
    public function tags(int $contactId): array
    {
        return $this->request(
            method: Method::GET,
            path: 'contacts/'.$contactId.'/contactTags',
            responseKey: 'contactTags'
        );
    }

    /**
     * Add a tag to a contact.
     *
     * @see https://developers.activecampaign.com/reference/create-contact-tag
     *
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    public function tag(int $contactId, int $tagId): array
    {
        return $this->request(
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
    }

    /**
     * Remove a tag from a contact.
     *
     * @see https://developers.activecampaign.com/reference/remove-a-contacts-tag
     *
     * @throws ActiveCampaignException
     */
    public function untag(int $contactId, int $tagId): void
    {
        $contactTagId = $this->getContactTagId($contactId, $tagId);

        throw_if(is_null($contactTagId), ActiveCampaignException::contactTagMissing($contactId, $tagId));

        $this->request(
            method: Method::DELETE,
            path: 'contactTags/'.$contactTagId
        );
    }

    /**
     * Remove a tag from a contact, ignoring a tag that is not applied.
     *
     * @see https://developers.activecampaign.com/reference/remove-a-contacts-tag
     *
     * @throws ActiveCampaignException
     */
    public function tryUntag(int $contactId, int $tagId): void
    {
        if ($this->getContactTagId($contactId, $tagId)) {
            $this->untag($contactId, $tagId);
        }
    }

    /**
     * Get the contactTag id of the association between a contact and a tag.
     *
     * @throws ActiveCampaignException
     */
    public function getContactTagId(int $contactId, int $tagId): ?int
    {
        foreach ($this->tags($contactId) as $contactTag) {
            if (isset($contactTag['tag']) && intval($contactTag['tag']) === $tagId) {
                return intval($contactTag['id']);
            }
        }

        return null;
    }

    /**
     * Subscribe or unsubscribe a contact from one or more lists.
     *
     * @see https://developers.activecampaign.com/reference/update-list-status-for-contact
     *
     * @param  array<int, ListStatus|int>  $listStatus  list id => status
     *
     * @throws ActiveCampaignException
     */
    public function updateListStatus(int $contactId, array $listStatus): void
    {
        foreach ($listStatus as $listId => $status) {
            $this->request(
                method: Method::POST,
                path: 'contactLists',
                options: [
                    'contactList' => [
                        'contact' => $contactId,
                        'list' => $listId,
                        'status' => $status instanceof ListStatus ? $status->value : $status,
                    ],
                ]
            );
        }
    }

    /**
     * Custom fields are declared by name in the config file, but the API only knows their ids.
     *
     * @param  array<string, mixed>  $contactRequest
     * @return array<string, mixed>
     *
     * @throws ActiveCampaignException
     */
    protected function requestCast(array $contactRequest): array
    {
        throw_if(empty($contactRequest['email']), ActiveCampaignException::missingField('contacts', 'email'));

        $contact = collect($contactRequest)->only(['email', 'firstName', 'lastName', 'phone'])->toArray();

        $fieldValues = collect(ActiveCampaignConfig::customFields())
            ->filter(fn ($customFieldId, $customFieldName) => ! empty($contactRequest[$customFieldName]))
            ->map(fn ($customFieldId, $customFieldName) => [
                'field' => strval($customFieldId),
                'value' => $contactRequest[$customFieldName],
            ])->values()->all();

        if ($fieldValues !== []) {
            $contact['fieldValues'] = $fieldValues;
        }

        return ['contact' => $contact];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function responseCast(array $response): array
    {
        $responseCast = $response['contact'];

        unset($responseCast['links']);

        $customFields = ActiveCampaignConfig::customFields();
        if (! empty($customFields) && ! empty($response['fieldValues'])) {
            $customFieldNames = array_flip($customFields);
            foreach ($response['fieldValues'] as $customField) {
                $customFieldId = intval($customField['field']);
                if (in_array($customFieldId, $customFields)) {
                    $responseCast[$customFieldNames[$customFieldId]] = $customField['value'];
                }
            }
        }

        return $responseCast;
    }
}
