<?php

use Datomatic\ActiveCampaign\Enums\ListStatus;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Illuminate\Support\Facades\Http;

it('lists contacts', function () {
    fakeActiveCampaign(['contacts' => [['id' => '1', 'email' => 'john@example.com']]]);

    $contacts = ActiveCampaign::contacts()->list();

    expect($contacts)->toHaveCount(1)
        ->and($contacts->first()['email'])->toBe('john@example.com');
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts');
});

it('appends the query string when listing', function () {
    fakeActiveCampaign(['contacts' => []]);

    ActiveCampaign::contacts()->list('email=john@example.com');

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts?email=john@example.com');
});

it('gets a contact', function () {
    fakeActiveCampaign(['contact' => ['id' => '1', 'email' => 'john@example.com', 'links' => ['x' => 'y']]]);

    expect(ActiveCampaign::contacts()->get(1))->toBe(['id' => '1', 'email' => 'john@example.com']);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts/1');
});

it('creates a contact', function () {
    fakeActiveCampaign(['contact' => ['id' => '1', 'email' => 'john@example.com']]);

    ActiveCampaign::contacts()->create([
        'email' => 'john@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'phone' => '123',
        'unknownKey' => 'dropped',
    ]);

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts')
        ->and(sentJson())->toBe([
            'contact' => [
                'email' => 'john@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'phone' => '123',
            ],
        ]);
});

it('refuses to create a contact without an email', function () {
    fakeActiveCampaign();

    ActiveCampaign::contacts()->create(['firstName' => 'John']);
})->throws(ActiveCampaignException::class, 'Missing required field "email" on contacts request');

it('syncs a contact on the singular contact/sync endpoint', function () {
    fakeActiveCampaign(['contact' => ['id' => '1', 'email' => 'john@example.com']]);

    ActiveCampaign::contacts()->sync(['email' => 'john@example.com']);

    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/contact/sync');
});

it('nests custom field values inside the contact object', function () {
    config()->set('active-campaign.custom_fields', ['is_email_verified' => 50, 'city' => 51]);
    fakeActiveCampaign(['contact' => ['id' => '1', 'email' => 'john@example.com']]);

    ActiveCampaign::contacts()->sync([
        'email' => 'john@example.com',
        'is_email_verified' => '1',
        'city' => '',
    ]);

    expect(sentJson())->toBe([
        'contact' => [
            'email' => 'john@example.com',
            'fieldValues' => [
                ['field' => '50', 'value' => '1'],
            ],
        ],
    ]);
});

it('maps custom field values back to their configured names', function () {
    config()->set('active-campaign.custom_fields', ['is_email_verified' => 50]);
    fakeActiveCampaign([
        'contact' => ['id' => '1', 'email' => 'john@example.com', 'links' => []],
        'fieldValues' => [
            ['field' => '50', 'value' => '1'],
            ['field' => '99', 'value' => 'ignored'],
        ],
    ]);

    expect(ActiveCampaign::contacts()->sync(['email' => 'john@example.com']))
        ->toBe(['id' => '1', 'email' => 'john@example.com', 'is_email_verified' => '1']);
});

it('updates a contact', function () {
    fakeActiveCampaign(['contact' => ['id' => '1', 'email' => 'john@example.com']]);

    ActiveCampaign::contacts()->update(1, ['email' => 'john@example.com']);

    expect(sentMethod())->toBe('PUT')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts/1');
});

it('deletes a contact', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::contacts()->delete(1);

    expect(sentMethod())->toBe('DELETE')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts/1');
});

it('wraps an api error into an ActiveCampaignException', function () {
    fakeActiveCampaign(['errors' => [['title' => 'No Result found for Subscriber with id 1']]], 404);

    ActiveCampaign::contacts()->get(1);
})->throws(
    ActiveCampaignException::class,
    'The request to "contacts/1" generated this error: [{"title":"No Result found for Subscriber with id 1"}]'
);

it('lists the tags of a contact', function () {
    fakeActiveCampaign(['contactTags' => [['id' => '10', 'contact' => '1', 'tag' => '5']]]);

    expect(ActiveCampaign::contacts()->tags(1))->toBe([['id' => '10', 'contact' => '1', 'tag' => '5']]);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts/1/contactTags');
});

it('tags a contact', function () {
    fakeActiveCampaign(['contactTag' => ['id' => '10', 'contact' => '1', 'tag' => '5']]);

    expect(ActiveCampaign::contacts()->tag(1, 5))->toBe(['id' => '10', 'contact' => '1', 'tag' => '5']);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contactTags')
        ->and(sentJson())->toBe(['contactTag' => ['contact' => 1, 'tag' => 5]]);
});

it('resolves the contactTag id from the tag key', function () {
    fakeActiveCampaign(['contactTags' => [
        ['id' => '9', 'contact' => '1', 'tag' => '4'],
        ['id' => '10', 'contact' => '1', 'tag' => '5'],
    ]]);

    expect(ActiveCampaign::contacts()->getContactTagId(1, 5))->toBe(10)
        ->and(ActiveCampaign::contacts()->getContactTagId(1, 99))->toBeNull();
});

it('untags a contact by deleting the contactTag association', function () {
    Http::fake([
        'test.api-us1.com/api/3/contacts/1/contactTags' => Http::response(['contactTags' => [
            ['id' => '10', 'contact' => '1', 'tag' => '5'],
        ]]),
        'test.api-us1.com/api/3/contactTags/10' => Http::response([]),
    ]);

    ActiveCampaign::contacts()->untag(1, 5);

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'https://test.api-us1.com/api/3/contactTags/10');
});

it('throws when untagging a tag the contact does not have', function () {
    fakeActiveCampaign(['contactTags' => []]);

    ActiveCampaign::contacts()->untag(1, 5);
})->throws(ActiveCampaignException::class, 'The tag 5 is missing on contact 1');

it('does not throw when tryUntag finds no tag', function () {
    fakeActiveCampaign(['contactTags' => []]);

    ActiveCampaign::contacts()->tryUntag(1, 5);

    Http::assertSentCount(1);
});

it('updates the list status of a contact', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::contacts()->updateListStatus(1, [
        2 => ListStatus::Subscribed,
        3 => ListStatus::Unsubscribed,
    ]);

    Http::assertSentCount(2);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contactLists')
        ->and(sentJson())->toBe(['contactList' => ['contact' => 1, 'list' => 2, 'status' => 1]]);

    $second = json_decode(Http::recorded()[1][0]->body(), true);
    expect($second)->toBe(['contactList' => ['contact' => 1, 'list' => 3, 'status' => 2]]);
});

it('accepts plain integers as list status', function () {
    fakeActiveCampaign([]);

    ActiveCampaign::contacts()->updateListStatus(1, [2 => 1]);

    expect(sentJson())->toBe(['contactList' => ['contact' => 1, 'list' => 2, 'status' => 1]]);
});
