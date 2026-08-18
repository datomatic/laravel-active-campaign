<?php

namespace Datomatic\ActiveCampaign;

use Datomatic\ActiveCampaign\Resources\ActiveCampaignAutomationsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignContactAutomationsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignContactsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldOptionsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldRelsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldValuesResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignListsResource;
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

    public function fieldOptions(): ActiveCampaignFieldOptionsResource
    {
        return resolve(ActiveCampaignFieldOptionsResource::class);
    }

    public function fieldRels(): ActiveCampaignFieldRelsResource
    {
        return resolve(ActiveCampaignFieldRelsResource::class);
    }

    public function lists(): ActiveCampaignListsResource
    {
        return resolve(ActiveCampaignListsResource::class);
    }

    public function automations(): ActiveCampaignAutomationsResource
    {
        return resolve(ActiveCampaignAutomationsResource::class);
    }

    public function contactAutomations(): ActiveCampaignContactAutomationsResource
    {
        return resolve(ActiveCampaignContactAutomationsResource::class);
    }

    public function tags(): ActiveCampaignTagsResource
    {
        return resolve(ActiveCampaignTagsResource::class);
    }
}
