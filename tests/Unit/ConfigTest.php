<?php

use Datomatic\ActiveCampaign\Exceptions\InvalidConfig;
use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;

it('reads every config param', function () {
    expect(ActiveCampaignConfig::baseUrl())->toBe('https://test.api-us1.com')
        ->and(ActiveCampaignConfig::apiKey())->toBe('test-api-key')
        ->and(ActiveCampaignConfig::timeout())->toBe(100)
        ->and(ActiveCampaignConfig::retryTimes())->toBe(1)
        ->and(ActiveCampaignConfig::retrySleep())->toBe(0)
        ->and(ActiveCampaignConfig::customFields())->toBe([]);
});

it('strips the trailing slash from the base url', function () {
    config()->set('active-campaign.base_url', 'https://test.api-us1.com/');

    expect(ActiveCampaignConfig::baseUrl())->toBe('https://test.api-us1.com');
});

it('throws when a param is missing', function () {
    config()->set('active-campaign.api_key', null);

    ActiveCampaignConfig::apiKey();
})->throws(InvalidConfig::class, 'You need to set api_key on active-campaign.php config file');

it('throws when a param has the wrong type', function (string $method, string $param, mixed $value, string $type) {
    config()->set('active-campaign.'.$param, $value);

    ActiveCampaignConfig::{$method}();
})->throws(InvalidConfig::class)->with([
    ['apiKey', 'api_key', 123, 'string'],
    ['timeout', 'timeout', 'abc', 'integer'],
    ['customFields', 'custom_fields', 'abc', 'array'],
]);
