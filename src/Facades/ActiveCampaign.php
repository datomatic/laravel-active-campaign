<?php

namespace Datomatic\ActiveCampaign\Facades;

use Datomatic\ActiveCampaign\Resources\ActiveCampaignContactsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldOptionsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldRelsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldValuesResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignTagsResource;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Datomatic\ActiveCampaign\ActiveCampaign
 *
 * @method static ActiveCampaignContactsResource contacts()
 * @method static ActiveCampaignFieldValuesResource fieldValues()
 * @method static ActiveCampaignFieldsResource fields()
 * @method static ActiveCampaignFieldOptionsResource fieldOptions()
 * @method static ActiveCampaignFieldRelsResource fieldRels()
 * @method static ActiveCampaignTagsResource tags()
 */
class ActiveCampaign extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Datomatic\ActiveCampaign\ActiveCampaign::class;
    }
}
