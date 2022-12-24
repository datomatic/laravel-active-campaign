<?php

namespace Datomatic\ActiveCampaign\Factories;

use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ActiveCampaignRequestFactory
{
    public static function make(): PendingRequest
    {
        return Http::withHeaders([
            'Api-Token' => ActiveCampaignConfig::apiKey(),
        ])
            ->acceptJson()
            ->baseUrl(ActiveCampaignConfig::baseUrl().'/api/3')
            ->timeout(ActiveCampaignConfig::timeout())
            ->retry(ActiveCampaignConfig::retryTimes(), ActiveCampaignConfig::retrySleep());
    }
}
