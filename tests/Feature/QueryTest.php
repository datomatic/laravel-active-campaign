<?php

use Datomatic\ActiveCampaign\Enums\FilterOperator;
use Datomatic\ActiveCampaign\Enums\ListStatus;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Datomatic\ActiveCampaign\Support\Query;
use Illuminate\Support\Facades\Http;

it('builds an equality filter', function () {
    expect(urldecode((string) Query::make()->filter('email', 'john@example.com')))
        ->toBe('filters[email]=john@example.com');
});

it('builds a filter with an operator', function () {
    expect(urldecode((string) Query::make()->filter('cdate', '2024-01-01', FilterOperator::GreaterThan)))
        ->toBe('filters[cdate][gt]=2024-01-01');
});

it('builds several filters at once', function () {
    expect(Query::make()->filters(['a' => 1, 'b' => 2])->toArray())
        ->toBe(['filters' => ['a' => 1, 'b' => 2]]);
});

it('builds orders', function () {
    expect(urldecode((string) Query::make()->orderBy('cdate')->orderByDesc('email')))
        ->toBe('orders[cdate]=ASC&orders[email]=DESC');
});

it('normalises an unknown sort direction to ascending', function () {
    expect(Query::make()->orderBy('cdate', 'sideways')->toArray())
        ->toBe(['orders' => ['cdate' => 'ASC']]);
});

it('accepts a lowercase sort direction', function () {
    expect(Query::make()->orderBy('cdate', 'desc')->toArray())
        ->toBe(['orders' => ['cdate' => 'DESC']]);
});

it('joins includes and drops duplicates', function () {
    expect((string) Query::make()->include('contactTags')->include('contactTags', 'contactLists'))
        ->toBe('include=contactTags%2CcontactLists');
});

it('carries top level params', function () {
    expect((string) Query::make()->where('search', 'john')->limit(50)->offset(10))
        ->toBe('search=john&limit=50&offset=10');
});

it('normalises values', function (mixed $value, mixed $expected) {
    expect(Query::make()->where('v', $value)->toArray()['v'])->toBe($expected);
})->with([
    'bool true' => [true, 1],
    'bool false' => [false, 0],
    'backed enum' => [ListStatus::Subscribed, 1],
    'array' => [[1, 2, 3], '1,2,3'],
    'string' => ['plain', 'plain'],
]);

it('normalises a date', function () {
    $query = Query::make()->filter('created_after', new DateTimeImmutable('2024-01-01 10:00:00', new DateTimeZone('UTC')));

    expect($query->toArray()['filters']['created_after'])->toBe('2024-01-01T10:00:00+00:00');
});

it('is accepted by list', function () {
    fakeActiveCampaign(['contacts' => []]);

    ActiveCampaign::contacts()->list(Query::make()->filter('email', 'john@example.com'));

    expect(urldecode(sentUrl()))
        ->toBe('https://test.api-us1.com/api/3/contacts?filters[email]=john@example.com');
});

it('is accepted by count', function () {
    fakeActiveCampaign(['contacts' => [], 'meta' => ['total' => '3']]);

    expect(ActiveCampaign::contacts()->count(Query::make()->filter('email', 'a@b.c')))->toBe(3);

    expect(urldecode(sentUrl()))
        ->toBe('https://test.api-us1.com/api/3/contacts?filters[email]=a@b.c&limit=1&offset=0');
});

it('is accepted by paginate', function () {
    fakeActiveCampaign(['tags' => [], 'meta' => ['total' => '0']]);

    ActiveCampaign::tags()->paginate(perPage: 10, page: 1, query: Query::make()->orderBy('tag'));

    expect(urldecode(sentUrl()))
        ->toBe('https://test.api-us1.com/api/3/tags?orders[tag]=ASC&limit=10&offset=0');
});

it('is accepted by all and carried onto every page', function () {
    Http::fakeSequence()
        ->push(['tags' => [['id' => '1'], ['id' => '2']]])
        ->push(['tags' => [['id' => '3']]]);

    ActiveCampaign::tags()->all(Query::make()->orderBy('tag'), perPage: 2);

    expect(urldecode(Http::recorded()[1][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/tags?orders[tag]=ASC&limit=2&offset=2');
});

it('makes a contacts query fall back to offset paging when it sets an order', function () {
    Http::fakeSequence()
        ->push(['contacts' => [['id' => '1'], ['id' => '2']]])
        ->push(['contacts' => [['id' => '3']]]);

    ActiveCampaign::contacts()->all(Query::make()->orderBy('email'), perPage: 2);

    expect(urldecode(Http::recorded()[1][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/contacts?orders[email]=ASC&limit=2&offset=2');
});

it('lets an explicit limit win over the one in the query object', function () {
    fakeActiveCampaign(['tags' => []]);

    ActiveCampaign::tags()->list(Query::make()->limit(50), limit: 5);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=5');
});
