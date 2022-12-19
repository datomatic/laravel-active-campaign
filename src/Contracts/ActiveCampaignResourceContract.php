<?php

namespace Datomatic\ActiveCampaign\Contracts;

/**
 * @property-read ActiveCampaignClientContract $client
 */
interface ActiveCampaignResourceContract
{
    public function client(): ActiveCampaignClientContract;
}
