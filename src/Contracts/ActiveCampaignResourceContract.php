<?php

namespace Datomatic\ActiveCampaign\Contracts;

interface ActiveCampaignResourceContract
{
    public function client(): ActiveCampaignClientContract;
}
