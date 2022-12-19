<?php

namespace Datomatic\ActiveCampaign\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Datomatic\ActiveCampaign\ActiveCampaign
 */
class ActiveCampaign extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Datomatic\ActiveCampaign\ActiveCampaign::class;
    }
}
