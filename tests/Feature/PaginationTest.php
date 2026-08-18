<?php

use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

/**
 * @return array<string, mixed>
 */
function tagPage(int $from, int $count, int $total): array
{
    return [
        'tags' => collect(range($from, $from + $count - 1))
            ->map(fn (int $id) => ['id' => (string) $id, 'tag' => 'tag-'.$id])
            ->all(),
        'meta' => ['total' => (string) $total],
    ];
}

it('sends no pagination params when none are asked for', function () {
    fakeActiveCampaign(tagPage(1, 3, 3));

    ActiveCampaign::tags()->list();

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags');
});

it('sends limit and offset when given', function () {
    fakeActiveCampaign(tagPage(1, 3, 3));

    ActiveCampaign::tags()->list(limit: 50, offset: 100);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=50&offset=100');
});

it('keeps a limit coming from the raw query string', function () {
    fakeActiveCampaign(tagPage(1, 3, 3));

    ActiveCampaign::tags()->list('limit=50');

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=50');
});

it('lets an explicit argument win over the raw query string', function () {
    fakeActiveCampaign(tagPage(1, 3, 3));

    ActiveCampaign::tags()->list('limit=50', limit: 10);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=10');
});

it('merges pagination params into a filter query', function () {
    fakeActiveCampaign(tagPage(1, 3, 3));

    ActiveCampaign::tags()->list('filters[tagType]=contact', limit: 100);

    expect(urldecode(sentUrl()))
        ->toBe('https://test.api-us1.com/api/3/tags?filters[tagType]=contact&limit=100');
});

it('reads the total from the meta object', function () {
    fakeActiveCampaign(tagPage(1, 1, 4312));

    expect(ActiveCampaign::tags()->count())->toBe(4312);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=1&offset=0');
});

it('returns zero when the endpoint reports no total', function () {
    fakeActiveCampaign(['tags' => []]);

    expect(ActiveCampaign::tags()->count())->toBe(0);
});

it('counts within a filtered query', function () {
    fakeActiveCampaign(tagPage(1, 1, 7));

    expect(ActiveCampaign::tags()->count('filters[tagType]=contact'))->toBe(7);
    expect(urldecode(sentUrl()))
        ->toBe('https://test.api-us1.com/api/3/tags?filters[tagType]=contact&limit=1&offset=0');
});

it('builds a length aware paginator', function () {
    fakeActiveCampaign(tagPage(21, 20, 55));

    $paginator = ActiveCampaign::tags()->paginate(perPage: 20, page: 2);

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->total())->toBe(55)
        ->and($paginator->perPage())->toBe(20)
        ->and($paginator->currentPage())->toBe(2)
        ->and($paginator->lastPage())->toBe(3)
        ->and($paginator->items())->toHaveCount(20)
        ->and($paginator->items()[0]['id'])->toBe('21');

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=20&offset=20');
});

it('clamps the page size to the api maximum', function () {
    fakeActiveCampaign(tagPage(1, 100, 100));

    ActiveCampaign::tags()->paginate(perPage: 500);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=100&offset=0');
});

it('never asks for a page below one record', function () {
    fakeActiveCampaign(tagPage(1, 1, 1));

    ActiveCampaign::tags()->paginate(perPage: 0, page: 0);

    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/tags?limit=1&offset=0');
});

it('walks every page lazily with offsets', function () {
    Http::fakeSequence()
        ->push(tagPage(1, 3, 7))
        ->push(tagPage(4, 3, 7))
        ->push(tagPage(7, 1, 7));

    $tags = ActiveCampaign::tags()->lazy(perPage: 3);

    expect($tags)->toBeInstanceOf(LazyCollection::class);

    expect($tags->pluck('id')->all())->toBe(['1', '2', '3', '4', '5', '6', '7']);
    Http::assertSentCount(3);
});

it('does not fetch anything until the lazy collection is consumed', function () {
    fakeActiveCampaign(tagPage(1, 1, 1));

    ActiveCampaign::tags()->lazy();

    Http::assertNothingSent();
});

it('stops after a short page', function () {
    Http::fakeSequence()->push(tagPage(1, 2, 2));

    expect(ActiveCampaign::tags()->lazy(perPage: 3)->count())->toBe(2);
    Http::assertSentCount(1);
});

it('makes one extra request when the last page is exactly full', function () {
    Http::fakeSequence()
        ->push(tagPage(1, 2, 2))
        ->push(['tags' => [], 'meta' => ['total' => '2']]);

    expect(ActiveCampaign::tags()->lazy(perPage: 2)->count())->toBe(2);
    Http::assertSentCount(2);
});

it('keeps distinct keys across pages', function () {
    Http::fakeSequence()
        ->push(tagPage(1, 2, 4))
        ->push(tagPage(3, 2, 4))
        ->push(['tags' => [], 'meta' => ['total' => '4']]);

    expect(ActiveCampaign::tags()->all(perPage: 2))->toHaveCount(4);
});

it('carries the caller query onto every page', function () {
    Http::fakeSequence()
        ->push(tagPage(1, 2, 4))
        ->push(tagPage(3, 1, 4));

    ActiveCampaign::tags()->all('filters[tagType]=contact', perPage: 2);

    expect(urldecode(Http::recorded()[1][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/tags?filters[tagType]=contact&limit=2&offset=2');
});
