<?php

namespace Datomatic\ActiveCampaign\Tests;

use Datomatic\ActiveCampaign\ActiveCampaignServiceProvider;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActiveCampaignServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('active-campaign.base_url', 'https://test.api-us1.com');
        config()->set('active-campaign.api_key', 'test-api-key');
        config()->set('active-campaign.retry_times', 1);
        config()->set('active-campaign.retry_sleep', 0);
    }
}
