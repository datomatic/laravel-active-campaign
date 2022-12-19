<?php

namespace Datomatic\ActiveCampaign\Factory;

use Datomatic\ActiveCampaign\ActiveCampaignClient;

class ActiveCampaignClientFactory
{
    public static function make(): ActiveCampaignClient
    {
        return new ActiveCampaignClient(ActiveCampaignRequestFactory::make());
    }
}
