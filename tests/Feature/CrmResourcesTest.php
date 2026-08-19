<?php

use Datomatic\ActiveCampaign\ActiveCampaign as ActiveCampaignManager;
use Datomatic\ActiveCampaign\Enums\NoteRelType;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignAccountContactsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignAccountsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignDealsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignDealStagesResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignNotesResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignPipelinesResource;
use Illuminate\Support\Facades\Http;

it('exposes every crm resource', function (string $method, string $class) {
    expect(resolve(ActiveCampaignManager::class)->{$method}())->toBeInstanceOf($class);
})->with([
    ['deals', ActiveCampaignDealsResource::class],
    ['dealStages', ActiveCampaignDealStagesResource::class],
    ['pipelines', ActiveCampaignPipelinesResource::class],
    ['accounts', ActiveCampaignAccountsResource::class],
    ['accountContacts', ActiveCampaignAccountContactsResource::class],
    ['notes', ActiveCampaignNotesResource::class],
]);

it('uses the right path and envelope for each crm resource', function (string $method, string $path, string $listKey, string $singleKey) {
    fakeActiveCampaign([
        $listKey => [['id' => '1']],
        $singleKey => ['id' => '1', 'links' => ['x' => 'y']],
        'meta' => ['total' => '1'],
    ]);

    expect(ActiveCampaign::{$method}()->list())->toHaveCount(1)
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/'.$path);

    expect(ActiveCampaign::{$method}()->get(1))->toBe(['id' => '1'])
        ->and(Http::recorded()[1][0]->url())->toBe('https://test.api-us1.com/api/3/'.$path.'/1');
})->with([
    ['deals', 'deals', 'deals', 'deal'],
    ['dealStages', 'dealStages', 'dealStages', 'dealStage'],
    ['pipelines', 'dealGroups', 'dealGroups', 'dealGroup'],
    ['accounts', 'accounts', 'accounts', 'account'],
    ['accountContacts', 'accountContacts', 'accountContacts', 'accountContact'],
    ['notes', 'notes', 'notes', 'note'],
]);

it('creates a deal', function () {
    fakeActiveCampaign(['deal' => ['id' => '1', 'title' => 'New business']]);

    ActiveCampaign::deals()->createDeal(
        title: 'New business',
        value: 150000,
        currency: 'EUR',
        groupId: 4,
        stageId: 9,
        ownerId: 1,
        contactId: 7,
    );

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/deals')
        ->and(sentJson())->toBe(['deal' => [
            'title' => 'New business',
            'value' => 150000,
            'currency' => 'eur',
            'group' => 4,
            'stage' => 9,
            'owner' => 1,
            'contact' => 7,
        ]]);
});

it('creates a deal against an account instead of a contact', function () {
    fakeActiveCampaign(['deal' => ['id' => '1']]);

    ActiveCampaign::deals()->createDeal(
        title: 'New business',
        value: 1,
        currency: 'usd',
        groupId: 4,
        stageId: 9,
        ownerId: 1,
        accountId: 3,
        attributes: ['status' => 0],
    );

    expect(sentJson()['deal'])->toBe([
        'title' => 'New business',
        'value' => 1,
        'currency' => 'usd',
        'group' => 4,
        'stage' => 9,
        'owner' => 1,
        'account' => 3,
        'status' => 0,
    ]);
});

it('refuses a deal with neither a contact nor an account', function () {
    fakeActiveCampaign(['deal' => []]);

    ActiveCampaign::deals()->createDeal('New business', 1, 'eur', 4, 9, 1);
})->throws(ActiveCampaignException::class, 'One of "contact", "account" is required on deals request');

it('creates a deal stage', function () {
    fakeActiveCampaign(['dealStage' => ['id' => '9']]);

    ActiveCampaign::dealStages()->createStage('Initial Contact', 4, ['order' => 1, 'color' => '32B0FC']);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/dealStages')
        ->and(sentJson())->toBe(['dealStage' => [
            'title' => 'Initial Contact',
            'group' => 4,
            'order' => 1,
            'color' => '32B0FC',
        ]]);
});

it('creates a pipeline', function () {
    fakeActiveCampaign(['dealGroup' => ['id' => '4']]);

    ActiveCampaign::pipelines()->createPipeline('Qualifications', ['currency' => 'eur', 'autoassign' => 1]);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/dealGroups')
        ->and(sentJson())->toBe(['dealGroup' => [
            'title' => 'Qualifications',
            'currency' => 'eur',
            'autoassign' => 1,
        ]]);
});

it('creates an account', function () {
    fakeActiveCampaign(['account' => ['id' => '3', 'name' => 'Example']]);

    ActiveCampaign::accounts()->createAccount('Example', ['accountUrl' => 'https://example.com']);

    expect(sentJson())->toBe(['account' => [
        'name' => 'Example',
        'accountUrl' => 'https://example.com',
    ]]);
});

it('associates a contact with an account', function () {
    fakeActiveCampaign(['accountContact' => ['id' => '5']]);

    ActiveCampaign::accountContacts()->associate(3, 2, 'Product Manager');

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/accountContacts')
        ->and(sentJson())->toBe(['accountContact' => [
            'account' => 3,
            'contact' => 2,
            'jobTitle' => 'Product Manager',
        ]]);
});

it('omits an absent job title', function () {
    fakeActiveCampaign(['accountContact' => ['id' => '5']]);

    ActiveCampaign::accounts()->addContact(3, 2);

    expect(sentJson())->toBe(['accountContact' => ['account' => 3, 'contact' => 2]]);
});

it('creates a note on a contact by default', function () {
    fakeActiveCampaign(['note' => ['id' => '1']]);

    ActiveCampaign::notes()->createNote('Called them back', 2);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/notes')
        ->and(sentJson())->toBe(['note' => [
            'note' => 'Called them back',
            'relid' => 2,
            'reltype' => 'Subscriber',
        ]]);
});

it('creates a note on any relation type', function (NoteRelType $type, string $expected) {
    fakeActiveCampaign(['note' => ['id' => '1']]);

    ActiveCampaign::notes()->createNote('text', 2, $type);

    expect(sentJson()['note']['reltype'])->toBe($expected);
})->with([
    [NoteRelType::Deal, 'Deal'],
    [NoteRelType::Account, 'CustomerAccount'],
    [NoteRelType::Contact, 'Subscriber'],
    [NoteRelType::DealTask, 'DealTask'],
    [NoteRelType::Activity, 'Activity'],
]);

it('paginates and filters crm resources like every other resource', function () {
    Http::fakeSequence()
        ->push(['deals' => [['id' => '1'], ['id' => '2']]])
        ->push(['deals' => [['id' => '3']]]);

    $deals = ActiveCampaign::deals()->all('filters[stage]=9', perPage: 2);

    expect($deals)->toHaveCount(3);
    expect(urldecode(Http::recorded()[1][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/deals?filters[stage]=9&limit=2&offset=2');
});

it('deletes crm records', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::deals()->delete(1);

    expect(sentMethod())->toBe('DELETE')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/deals/1');
});
