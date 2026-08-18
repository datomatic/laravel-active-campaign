<?php

use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignAutomationsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignContactAutomationsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignListsResource;
use Illuminate\Support\Facades\Http;

it('lists automations', function () {
    fakeActiveCampaign(['automations' => [
        ['id' => '1', 'name' => 'Welcome series'],
    ], 'meta' => ['total' => '1']]);

    expect(ActiveCampaign::automations()->list())->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/automations');
});

it('counts automations', function () {
    fakeActiveCampaign(['automations' => [['id' => '1']], 'meta' => ['total' => '12']]);

    expect(ActiveCampaign::automations()->count())->toBe(12);
});

it('gets an automation', function () {
    fakeActiveCampaign(['automation' => ['id' => '1', 'name' => 'Welcome series', 'links' => []]]);

    expect(ActiveCampaign::automations()->get(1))->toBe(['id' => '1', 'name' => 'Welcome series']);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/automations/1');
});

it('lists the automations a contact is in', function () {
    fakeActiveCampaign(['contactAutomations' => [
        ['id' => '9', 'contact' => '1', 'automation' => '42'],
    ]]);

    expect(ActiveCampaign::contacts()->automations(1))->toBe([
        ['id' => '9', 'contact' => '1', 'automation' => '42'],
    ]);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts/1/contactAutomations');
});

it('adds a contact to an automation', function () {
    fakeActiveCampaign(['contactAutomation' => ['id' => '9', 'contact' => '1', 'automation' => '42']]);

    $result = ActiveCampaign::contacts()->addToAutomation(1, 42);

    expect($result)->toBe(['id' => '9', 'contact' => '1', 'automation' => '42']);
    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/contactAutomations')
        ->and(sentJson())->toBe(['contactAutomation' => ['contact' => 1, 'automation' => 42]]);
});

it('adds a contact to an automation through its own resource', function () {
    fakeActiveCampaign(['contactAutomation' => ['id' => '9', 'links' => ['x' => 'y']]]);

    expect(ActiveCampaign::contactAutomations()->add(1, 42))->toBe(['id' => '9']);
    expect(sentJson())->toBe(['contactAutomation' => ['contact' => 1, 'automation' => 42]]);
});

it('resolves the contactAutomation id', function () {
    fakeActiveCampaign(['contactAutomations' => [
        ['id' => '8', 'automation' => '41'],
        ['id' => '9', 'automation' => '42'],
    ]]);

    expect(ActiveCampaign::contacts()->getContactAutomationId(1, 42))->toBe(9)
        ->and(ActiveCampaign::contacts()->getContactAutomationId(1, 99))->toBeNull();
});

it('removes a contact from an automation by deleting the association', function () {
    Http::fake([
        'test.api-us1.com/api/3/contacts/1/contactAutomations' => Http::response([
            'contactAutomations' => [['id' => '9', 'automation' => '42']],
        ]),
        'test.api-us1.com/api/3/contactAutomations/9' => Http::response([]),
    ]);

    ActiveCampaign::contacts()->removeFromAutomation(1, 42);

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'https://test.api-us1.com/api/3/contactAutomations/9');
});

it('throws when removing an automation the contact is not in', function () {
    fakeActiveCampaign(['contactAutomations' => []]);

    ActiveCampaign::contacts()->removeFromAutomation(1, 42);
})->throws(ActiveCampaignException::class, 'The automation 42 is missing on contact 1');

it('does not throw when tryRemoveFromAutomation finds nothing', function () {
    fakeActiveCampaign(['contactAutomations' => []]);

    ActiveCampaign::contacts()->tryRemoveFromAutomation(1, 42);

    Http::assertSentCount(1);
});

it('exposes the new resources', function (string $method, string $class) {
    expect(resolve(Datomatic\ActiveCampaign\ActiveCampaign::class)->{$method}())->toBeInstanceOf($class);
})->with([
    ['lists', ActiveCampaignListsResource::class],
    ['automations', ActiveCampaignAutomationsResource::class],
    ['contactAutomations', ActiveCampaignContactAutomationsResource::class],
]);
