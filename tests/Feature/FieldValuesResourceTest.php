<?php

use Datomatic\ActiveCampaign\Facades\ActiveCampaign;

it('lists field values', function () {
    fakeActiveCampaign(['fieldValues' => [['id' => '1', 'value' => 'Rome']]]);

    expect(ActiveCampaign::fieldValues()->list())->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldValues');
});

it('gets a field value', function () {
    fakeActiveCampaign(['fieldValue' => ['id' => '1', 'value' => 'Rome', 'links' => []]]);

    expect(ActiveCampaign::fieldValues()->get(1))->toBe(['id' => '1', 'value' => 'Rome']);
});

it('creates a field value with the required contact id', function () {
    fakeActiveCampaign(['fieldValue' => ['id' => '1', 'value' => 'Rome']]);

    ActiveCampaign::fieldValues()->createFieldValue(7, 3, 'Rome');

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldValues')
        ->and(sentJson())->toBe(['fieldValue' => [
            'contact' => 7,
            'field' => 3,
            'value' => 'Rome',
        ]]);
});

it('updates a field value', function () {
    fakeActiveCampaign(['fieldValue' => ['id' => '1', 'value' => 'Milan']]);

    ActiveCampaign::fieldValues()->updateFieldValue(1, 3, 'Milan');

    expect(sentMethod())->toBe('PUT')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldValues/1')
        ->and(sentJson())->toBe(['fieldValue' => ['field' => 3, 'value' => 'Milan']]);
});

it('deletes a field value', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::fieldValues()->delete(1);

    expect(sentMethod())->toBe('DELETE')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldValues/1');
});
