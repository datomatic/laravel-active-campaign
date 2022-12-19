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
        return new self('The tag '.$tagId.' missing on contact'.$contactId);
    }

    public static function requestError(string $path, array $result): self
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
