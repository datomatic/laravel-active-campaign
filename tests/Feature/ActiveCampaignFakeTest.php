<?php

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Datomatic\ActiveCampaign\Testing\ActiveCampaignFake;

it('builds a list response the resources understand', function () {
    ActiveCampaignFake::fake([
        'contacts' => ActiveCampaignFake::list('contacts', [
            ['id' => '1', 'email' => 'john@example.com'],
        ], total: 42),
    ]);

    expect(ActiveCampaign::contacts()->list())->toHaveCount(1)
        ->and(ActiveCampaign::contacts()->count())->toBe(42);
});

it('builds a single response the resources understand', function () {
    ActiveCampaignFake::fake([
        'contacts/1' => ActiveCampaignFake::single('contact', ['id' => '1', 'email' => 'john@example.com']),
    ]);

    expect(ActiveCampaign::contacts()->get(1))->toBe(['id' => '1', 'email' => 'john@example.com']);
});

it('supports sideloaded keys', function () {
    config()->set('active-campaign.custom_fields', ['city' => 50]);

    ActiveCampaignFake::fake([
        'contact/sync' => ActiveCampaignFake::single(
            'contact',
            ['id' => '1', 'email' => 'john@example.com'],
            ['fieldValues' => [['field' => '50', 'value' => 'Rome']]],
        ),
    ]);

    expect(ActiveCampaign::contacts()->sync(['email' => 'john@example.com']))
        ->toBe(['id' => '1', 'email' => 'john@example.com', 'city' => 'Rome']);
});

it('answers unlisted paths with an empty 200', function () {
    ActiveCampaignFake::fake();

    expect(ActiveCampaign::tags()->list())->toBeEmpty();
    ActiveCampaignFake::assertSent(Method::GET, 'tags');
});

it('matches a path with a wildcard', function () {
    ActiveCampaignFake::fake([
        'contacts/*' => ActiveCampaignFake::single('contact', ['id' => '7']),
    ]);

    expect(ActiveCampaign::contacts()->get(7))->toBe(['id' => '7']);
});

it('builds an error response that surfaces as an exception', function () {
    ActiveCampaignFake::fake([
        'contacts/1' => ActiveCampaignFake::error(['No Result found for Subscriber with id 1'], 404),
    ]);

    ActiveCampaign::contacts()->get(1);
})->throws(ActiveCampaignException::class, 'No Result found for Subscriber with id 1');

it('asserts on method and path', function () {
    ActiveCampaignFake::fake();

    ActiveCampaign::contacts()->delete(3);

    ActiveCampaignFake::assertSent(Method::DELETE, 'contacts/3');
    ActiveCampaignFake::assertNotSent(Method::POST, 'contacts/3');
    ActiveCampaignFake::assertNotSent(Method::DELETE, 'contacts/4');
    ActiveCampaignFake::assertSentCount(1);
});

it('ignores the query string when matching a path', function () {
    ActiveCampaignFake::fake();

    ActiveCampaign::tags()->list('filters[tagType]=contact');

    ActiveCampaignFake::assertSent(Method::GET, 'tags');
});

it('asserts on the json body', function () {
    ActiveCampaignFake::fake([
        'contactTags' => ActiveCampaignFake::single('contactTag', ['id' => '9']),
    ]);

    ActiveCampaign::contacts()->tag(1, 5);

    ActiveCampaignFake::assertSentJson(Method::POST, 'contactTags', [
        'contactTag' => ['contact' => 1, 'tag' => 5],
    ]);
});

it('exposes the recorded requests and the nothing-sent assertion', function () {
    ActiveCampaignFake::fake();

    ActiveCampaignFake::assertNothingSent();

    ActiveCampaign::tags()->get(1);

    expect(ActiveCampaignFake::recorded())->toHaveCount(1)
        ->and(ActiveCampaignFake::recorded()[0]->url())->toBe('https://test.api-us1.com/api/3/tags/1');
});
