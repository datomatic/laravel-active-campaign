<?php

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Illuminate\Support\Facades\Http;

it('sends the api token and the json accept header', function () {
    fakeActiveCampaign(['tags' => []]);

    resolve(ActiveCampaignClientContract::class)->send(Method::GET, 'tags');

    Http::assertSent(fn ($request) => $request->hasHeader('Api-Token', 'test-api-key')
        && $request->hasHeader('Accept', 'application/json'));
});

it('prefixes every url with the api version', function () {
    fakeActiveCampaign(['tags' => []]);

    resolve(ActiveCampaignClientContract::class)->send(Method::GET, 'tags');

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags');
});

it('does not send a body when there is no data', function () {
    fakeActiveCampaign([]);

    resolve(ActiveCampaignClientContract::class)->send(Method::DELETE, 'tags/1');

    expect(Http::recorded()[0][0]->body())->toBe('');
});

it('sends the data as json', function () {
    fakeActiveCampaign([]);

    resolve(ActiveCampaignClientContract::class)->send(Method::POST, 'tags', ['tag' => ['tag' => 'test']]);

    expect(sentJson())->toBe(['tag' => ['tag' => 'test']]);
});

it('does not throw on a failed response', function () {
    fakeActiveCampaign(['message' => 'No Result found'], 404);

    $response = resolve(ActiveCampaignClientContract::class)->send(Method::GET, 'tags/1');

    expect($response->status())->toBe(404);
});

it('retries server errors and gives up after the configured attempts', function () {
    config()->set('active-campaign.retry_times', 3);

    Http::fake(['test.api-us1.com/*' => Http::response(['message' => 'boom'], 500)]);

    $response = resolve(ActiveCampaignClientContract::class)->send(Method::GET, 'tags');

    expect($response->status())->toBe(500);
    Http::assertSentCount(3);
});

it('does not retry client errors', function () {
    config()->set('active-campaign.retry_times', 3);

    Http::fake(['test.api-us1.com/*' => Http::response(['message' => 'No Result found'], 404)]);

    resolve(ActiveCampaignClientContract::class)->send(Method::GET, 'tags/1');

    Http::assertSentCount(1);
});
