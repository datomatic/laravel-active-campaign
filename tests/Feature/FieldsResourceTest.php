<?php

use Datomatic\ActiveCampaign\Enums\FieldType;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;

it('lists fields', function () {
    fakeActiveCampaign(['fields' => [['id' => '1', 'title' => 'City']]]);

    expect(ActiveCampaign::fields()->list())->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/fields');
});

it('gets a field', function () {
    fakeActiveCampaign(['field' => ['id' => '1', 'title' => 'City', 'links' => []]]);

    expect(ActiveCampaign::fields()->get(1))->toBe(['id' => '1', 'title' => 'City']);
});

it('creates a text field by default', function () {
    fakeActiveCampaign(['field' => ['id' => '1', 'title' => 'City']]);

    ActiveCampaign::fields()->createField('City');

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fields')
        ->and(sentJson())->toBe(['field' => ['title' => 'City', 'type' => 'text']]);
});

it('creates a field of any type with extra attributes', function () {
    fakeActiveCampaign(['field' => ['id' => '1', 'title' => 'Birthday']]);

    ActiveCampaign::fields()->createField('Birthday', FieldType::Date, ['perstag' => 'BIRTHDAY', 'visible' => 1]);

    expect(sentJson())->toBe(['field' => [
        'title' => 'Birthday',
        'type' => 'date',
        'perstag' => 'BIRTHDAY',
        'visible' => 1,
    ]]);
});

it('updates a field', function () {
    fakeActiveCampaign(['field' => ['id' => '1', 'title' => 'Town']]);

    ActiveCampaign::fields()->updateField(1, 'Town');

    expect(sentMethod())->toBe('PUT')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fields/1')
        ->and(sentJson())->toBe(['field' => ['title' => 'Town', 'type' => 'text']]);
});

it('deletes a field', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::fields()->delete(1);

    expect(sentMethod())->toBe('DELETE')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fields/1');
});
