<?php

namespace Datomatic\ActiveCampaign\Support;

class ActiveCampaignConfig extends Config
{
    public static function baseUrl(): string
    {
        return rtrim(self::getStringParam('base_url'), '/');
    }

    public static function apiKey(): string
    {
        return self::getStringParam('api_key');
    }

    public static function timeout(): int
    {
        return self::getIntParam('timeout');
    }

    public static function retryTimes(): int
    {
        return self::getIntParam('retry_times');
    }

    public static function retrySleep(): int
    {
        return self::getIntParam('retry_sleep');
    }

    /**
     * @return array<string, int|string>
     */
    public static function customFields(): array
    {
        return self::getArrayParam('custom_fields');
    }
}
