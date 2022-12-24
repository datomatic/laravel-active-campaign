<?php

namespace Datomatic\ActiveCampaign\Resources;

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;
use Illuminate\Http\Client\RequestException;

class ActiveCampaignContactsResource extends ActiveCampaignResource
{
    protected string $resourceBasePath = 'contacts';

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
            options: $this->requestCast($contactArray),
        );

        return $this->responseCast($contact);
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
     * @param  array  $contactRequest
     * @return mixed[]
     */
    protected function requestCast(array $contactRequest): array
    {
        throw_if(empty($contactRequest['email']), ActiveCampaignException::missingField('contacts', 'email'));

        $requestArray = [];
        $requestArray['contact'] = collect($contactRequest)->only(['email', 'firstName', 'lastName', 'phone'])->toArray();
        $requestArray['fieldValues'] = collect(ActiveCampaignConfig::customFields())
            ->filter(fn ($customFieldId, $customFieldName) => ! empty($contactRequest[$customFieldName]))
            ->map(fn ($customFieldId, $customFieldName) => [
                'field' => strval($customFieldId),
                'value' => $contactRequest[$customFieldName],
            ])->all();

        return $requestArray;
    }

    protected function responseCast(array $response): array
    {
        $responseCast = $response['contact'];

        unset($responseCast['links']);

        $customFields = ActiveCampaignConfig::customFields();
        if (! empty($customFields)) {
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
