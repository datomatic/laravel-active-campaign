<?php

namespace Datomatic\ActiveCampaign;

use Datomatic\ActiveCampaign\Resources\ActiveCampaignContactsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldValuesResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignTagsResource;

class ActiveCampaign
{
    public function contacts(): ActiveCampaignContactsResource
    {
        return resolve(ActiveCampaignContactsResource::class);
    }

    public function fieldValues(): ActiveCampaignFieldValuesResource
    {
        return resolve(ActiveCampaignFieldValuesResource::class);
    }

    public function fields(): ActiveCampaignFieldsResource
    {
        return resolve(ActiveCampaignFieldsResource::class);
    }

    public function tags(): ActiveCampaignTagsResource
    {
        return resolve(ActiveCampaignTagsResource::class);
    }
}
