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
        return new ActiveCampaignContactsResource($this);
    }

    public function fieldValues(): ActiveCampaignFieldValuesResource
    {
        return new ActiveCampaignFieldValuesResource($this);
    }

    public function fields(): ActiveCampaignFieldsResource
    {
        return new ActiveCampaignFieldsResource($this);
    }

    public function tags(): ActiveCampaignTagsResource
    {
        return new ActiveCampaignTagsResource($this);
    }
}
