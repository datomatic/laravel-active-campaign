<?php

namespace Datomatic\ActiveCampaign\Exceptions;

use Exception;

class ActiveCampaignException extends Exception
{
    public static function missingField(string $path, string $field): self
    {
        return new self('Missing required field "'.$field.'" on '.$path.' request');
    }

    public static function contactTagMissing(int $contactId, int $tagId): self
    {
        return new self('The tag '.$tagId.' is missing on contact '.$contactId);
    }

    public static function batchTooLarge(int $size, int $max): self
    {
        return new self('A bulk import accepts at most '.$max.' contacts per request, '.$size.' given');
    }

    public static function contactAutomationMissing(int $contactId, int $automationId): self
    {
        return new self('The automation '.$automationId.' is missing on contact '.$contactId);
    }

    /**
     * @param  array<mixed>|null  $result
     */
    public static function requestError(string $path, ?array $result = null): self
    {
        if (isset($result['errors'])) {
            $error = json_encode($result['errors']);
        } elseif (isset($result['message'])) {
            $error = $result['message'];
        } else {
            $error = json_encode($result);
        }

        return new self('The request to "'.$path.'" generated this error: '.$error);
    }
}
