<?php

use Datomatic\ActiveCampaign\Enums\FieldType;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldOptionsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldRelsResource;
use Illuminate\Support\Facades\Http;

it('knows which field types need options', function (FieldType $type, bool $expected) {
    expect($type->requiresOptions())->toBe($expected);
})->with([
    [FieldType::DropDown, true],
    [FieldType::MultiSelect, true],
    [FieldType::Radio, true],
    [FieldType::CheckBox, true],
    [FieldType::ListBox, true],
    [FieldType::Text, false],
    [FieldType::TextArea, false],
    [FieldType::Date, false],
    [FieldType::DateTime, false],
    [FieldType::Hidden, false],
]);

it('creates options through the bulk endpoint', function () {
    fakeActiveCampaign(['fieldOptions' => [
        ['id' => '1', 'field' => '34', 'value' => 'Sales'],
        ['id' => '2', 'field' => '34', 'value' => 'Engineering'],
    ]]);

    $created = ActiveCampaign::fields()->createOptions(34, ['Sales', 'Engineering']);

    expect($created)->toHaveCount(2)
        ->and($created->first()['id'])->toBe('1');

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldOption/bulk')
        ->and(sentJson())->toBe(['fieldOptions' => [
            ['field' => 34, 'orderid' => 1, 'value' => 'Sales', 'label' => 'Sales'],
            ['field' => 34, 'orderid' => 2, 'value' => 'Engineering', 'label' => 'Engineering'],
        ]]);
});

it('lets a full option array override the defaults', function () {
    fakeActiveCampaign(['fieldOptions' => []]);

    ActiveCampaign::fields()->createOptions(34, [
        ['value' => 'a', 'label' => 'Option A', 'isdefault' => true],
        ['value' => 'b', 'orderid' => 9],
    ]);

    expect(sentJson())->toBe(['fieldOptions' => [
        ['field' => 34, 'orderid' => 1, 'value' => 'a', 'label' => 'Option A', 'isdefault' => true],
        ['field' => 34, 'orderid' => 9, 'value' => 'b', 'label' => 'b'],
    ]]);
});

it('sends a single option as a one element bulk call', function () {
    fakeActiveCampaign(['fieldOptions' => [['id' => '1', 'value' => 'Sales']]]);

    $option = ActiveCampaign::fieldOptions()->create(['field' => 34, 'value' => 'Sales']);

    expect($option)->toBe(['id' => '1', 'value' => 'Sales'])
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldOption/bulk')
        ->and(sentJson())->toBe(['fieldOptions' => [['field' => 34, 'value' => 'Sales']]]);
});

it('returns an empty array when a bulk create sends nothing back', function () {
    fakeActiveCampaign(['fieldOptions' => []]);

    expect(ActiveCampaign::fieldOptions()->create(['field' => 34, 'value' => 'Sales']))->toBe([]);
});

it('reads the options of a field', function () {
    fakeActiveCampaign(['fieldOptions' => [['id' => '1', 'value' => 'Sales']]]);

    expect(ActiveCampaign::fields()->options(34))->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/fields/34/options');
});

it('relates a field to a list', function () {
    fakeActiveCampaign(['fieldRel' => ['id' => '7', 'field' => '34', 'relid' => '1']]);

    $rel = ActiveCampaign::fields()->relate(34, 1);

    expect($rel)->toBe(['id' => '7', 'field' => '34', 'relid' => '1']);
    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldRels')
        ->and(sentJson())->toBe(['fieldRel' => ['field' => 34, 'relid' => 1]]);
});

it('relates a field to every list by default', function () {
    fakeActiveCampaign(['fieldRel' => ['id' => '7']]);

    ActiveCampaign::fields()->relate(34);

    expect(sentJson())->toBe(['fieldRel' => ['field' => 34, 'relid' => ActiveCampaignFieldRelsResource::ALL_LISTS]]);
});

it('reads the relations of a field', function () {
    fakeActiveCampaign(['fieldRels' => [['id' => '7', 'relid' => '1']]]);

    expect(ActiveCampaign::fields()->relations(34))->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/fields/34/relations');
});

it('strips links from a field relation', function () {
    fakeActiveCampaign(['fieldRel' => ['id' => '7', 'relid' => '1', 'links' => ['x' => 'y']]]);

    expect(ActiveCampaign::fieldRels()->relate(34, 1))->toBe(['id' => '7', 'relid' => '1']);
});

it('creates a usable dropdown in one call', function () {
    Http::fakeSequence()
        ->push(['field' => ['id' => '34', 'title' => 'Department', 'type' => 'dropdown']])
        ->push(['fieldOptions' => [['id' => '1'], ['id' => '2']]])
        ->push(['fieldRel' => ['id' => '7']])
        ->push(['fieldRel' => ['id' => '8']]);

    $field = ActiveCampaign::fields()->createField(
        'Department',
        FieldType::DropDown,
        options: ['Sales', 'Engineering'],
        lists: [1, 2],
    );

    expect($field['id'])->toBe('34');
    Http::assertSentCount(4);

    $recorded = Http::recorded();

    expect($recorded[0][0]->url())->toBe('https://test.api-us1.com/api/3/fields')
        ->and(json_decode($recorded[0][0]->body(), true))
        ->toBe(['field' => ['title' => 'Department', 'type' => 'dropdown']]);

    expect($recorded[1][0]->url())->toBe('https://test.api-us1.com/api/3/fieldOption/bulk')
        ->and(json_decode($recorded[1][0]->body(), true)['fieldOptions'])->toHaveCount(2);

    expect(json_decode($recorded[2][0]->body(), true))
        ->toBe(['fieldRel' => ['field' => 34, 'relid' => 1]])
        ->and(json_decode($recorded[3][0]->body(), true))
        ->toBe(['fieldRel' => ['field' => 34, 'relid' => 2]]);
});

it('creates a plain field without touching options or relations', function () {
    fakeActiveCampaign(['field' => ['id' => '34', 'title' => 'City']]);

    ActiveCampaign::fields()->createField('City');

    Http::assertSentCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/fields');
});

it('exposes the field options and field rels resources', function (string $method, string $class) {
    expect(resolve(Datomatic\ActiveCampaign\ActiveCampaign::class)->{$method}())->toBeInstanceOf($class);
})->with([
    ['fieldOptions', ActiveCampaignFieldOptionsResource::class],
    ['fieldRels', ActiveCampaignFieldRelsResource::class],
]);

it('paginates field options and field rels like every other resource', function () {
    fakeActiveCampaign(['fieldOptions' => [['id' => '1']], 'meta' => ['total' => '42']]);

    expect(ActiveCampaign::fieldOptions()->count())->toBe(42);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/fieldOptions?limit=1&offset=0');
});
