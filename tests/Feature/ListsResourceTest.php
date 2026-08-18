<?php

use Datomatic\ActiveCampaign\Enums\ListStatus;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Illuminate\Support\Facades\Http;

it('lists the lists', function () {
    fakeActiveCampaign(['lists' => [
        ['id' => '1', 'name' => 'Newsletter'],
        ['id' => '2', 'name' => 'Product updates'],
    ], 'meta' => ['total' => '2']]);

    expect(ActiveCampaign::lists()->list())->toHaveCount(2);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/lists');
});

it('filters lists by name', function () {
    fakeActiveCampaign(['lists' => []]);

    ActiveCampaign::lists()->list('filters[name]=News');

    expect(urldecode(sentUrl()))->toBe('https://test.api-us1.com/api/3/lists?filters[name]=News');
});

it('counts lists', function () {
    fakeActiveCampaign(['lists' => [['id' => '1']], 'meta' => ['total' => '9']]);

    expect(ActiveCampaign::lists()->count())->toBe(9);
});

it('gets a list', function () {
    fakeActiveCampaign(['list' => ['id' => '1', 'name' => 'Newsletter', 'links' => ['x' => 'y']]]);

    expect(ActiveCampaign::lists()->get(1))->toBe(['id' => '1', 'name' => 'Newsletter']);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/lists/1');
});

it('creates a list with the four required fields', function () {
    fakeActiveCampaign(['list' => ['id' => '1', 'name' => 'Newsletter']]);

    ActiveCampaign::lists()->createList(
        'Newsletter',
        'newsletter',
        'https://example.com',
        'You subscribed on our website.',
    );

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/lists')
        ->and(sentJson())->toBe(['list' => [
            'name' => 'Newsletter',
            'stringid' => 'newsletter',
            'sender_url' => 'https://example.com',
            'sender_reminder' => 'You subscribed on our website.',
        ]]);
});

it('passes extra list attributes through', function () {
    fakeActiveCampaign(['list' => ['id' => '1']]);

    ActiveCampaign::lists()->createList('N', 'n', 'https://example.com', 'why', [
        'user' => 1,
        'send_last_broadcast' => 0,
    ]);

    expect(sentJson()['list'])->toHaveKeys(['user', 'send_last_broadcast'])
        ->and(sentJson()['list']['user'])->toBe(1);
});

it('deletes a list', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::lists()->delete(1);

    expect(sentMethod())->toBe('DELETE')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/lists/1');
});

it('reads the list subscriptions of a contact', function () {
    fakeActiveCampaign(['contactLists' => [
        ['id' => '5', 'contact' => '1', 'list' => '2', 'status' => '1'],
    ]]);

    expect(ActiveCampaign::contacts()->lists(1))->toBe([
        ['id' => '5', 'contact' => '1', 'list' => '2', 'status' => '1'],
    ]);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts/1/contactLists');
});

it('closes the loop between reading and writing a subscription', function () {
    Http::fake([
        'test.api-us1.com/api/3/contactLists' => Http::response([]),
        'test.api-us1.com/api/3/contacts/1/contactLists' => Http::response([
            'contactLists' => [['id' => '5', 'list' => '2', 'status' => '1']],
        ]),
    ]);

    ActiveCampaign::contacts()->updateListStatus(1, [2 => ListStatus::Unsubscribed]);
    $subscriptions = ActiveCampaign::contacts()->lists(1);

    expect($subscriptions)->toHaveCount(1);
    Http::assertSentCount(2);
});
