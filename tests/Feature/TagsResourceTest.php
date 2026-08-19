<?php

use Datomatic\ActiveCampaign\Enums\TagType;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;

it('lists tags', function () {
    fakeActiveCampaign(['tags' => [['id' => '1', 'tag' => 'customer']]]);

    expect(ActiveCampaign::tags()->list())->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags');
});

it('gets a tag', function () {
    fakeActiveCampaign(['tag' => ['id' => '1', 'tag' => 'customer', 'links' => []]]);

    expect(ActiveCampaign::tags()->get(1))->toBe(['id' => '1', 'tag' => 'customer']);
});

it('creates a tag with the contact type by default', function () {
    fakeActiveCampaign(['tag' => ['id' => '1', 'tag' => 'customer']]);

    ActiveCampaign::tags()->createTag('customer', 'a paying user');

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/tags')
        ->and(sentJson())->toBe(['tag' => [
            'tag' => 'customer',
            'description' => 'a paying user',
            'tagType' => 'contact',
        ]]);
});

it('creates a template tag', function () {
    fakeActiveCampaign(['tag' => ['id' => '1', 'tag' => 'header']]);

    ActiveCampaign::tags()->createTag('header', '', TagType::Template);

    expect(sentJson()['tag']['tagType'])->toBe('template');
});

it('defaults the tag type when creating through the generic create method', function () {
    fakeActiveCampaign(['tag' => ['id' => '1', 'tag' => 'customer']]);

    ActiveCampaign::tags()->create(['tag' => 'customer']);

    expect(sentJson())->toBe(['tag' => ['tag' => 'customer', 'tagType' => 'contact']]);
});

it('updates a tag', function () {
    fakeActiveCampaign(['tag' => ['id' => '1', 'tag' => 'customer']]);

    ActiveCampaign::tags()->updateTag(1, 'customer', 'updated');

    expect(sentMethod())->toBe('PUT')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/tags/1')
        ->and(sentJson())->toBe(['tag' => [
            'tag' => 'customer',
            'description' => 'updated',
            'tagType' => 'contact',
        ]]);
});

it('deletes a tag', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::tags()->delete(1);

    expect(sentMethod())->toBe('DELETE')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/tags/1');
});
