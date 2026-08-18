<?php

use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Illuminate\Support\Facades\Http;

/**
 * @return array<string, mixed>
 */
function contactPage(int $from, int $count, int $total): array
{
    return [
        'contacts' => collect($count > 0 ? range($from, $from + $count - 1) : [])
            ->map(fn (int $id) => ['id' => (string) $id, 'email' => 'user'.$id.'@example.com'])
            ->all(),
        'meta' => ['total' => (string) $total],
    ];
}

it('walks contacts by id instead of by offset', function () {
    Http::fakeSequence()
        ->push(contactPage(1, 2, 5))
        ->push(contactPage(3, 2, 5))
        ->push(contactPage(5, 1, 5));

    $emails = ActiveCampaign::contacts()->lazy(perPage: 2)->pluck('id')->all();

    expect($emails)->toBe(['1', '2', '3', '4', '5']);
    Http::assertSentCount(3);

    expect(urldecode(Http::recorded()[0][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/contacts?orders[id]=ASC&id_greater=0&limit=2')
        ->and(urldecode(Http::recorded()[1][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/contacts?orders[id]=ASC&id_greater=2&limit=2')
        ->and(urldecode(Http::recorded()[2][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/contacts?orders[id]=ASC&id_greater=4&limit=2');
});

it('carries the caller filters onto every contact page', function () {
    Http::fakeSequence()
        ->push(contactPage(1, 2, 3))
        ->push(contactPage(3, 1, 3));

    ActiveCampaign::contacts()->all('filters[created_after]=2024-01-01', perPage: 2);

    expect(urldecode(Http::recorded()[1][0]->url()))->toBe(
        'https://test.api-us1.com/api/3/contacts?filters[created_after]=2024-01-01&orders[id]=ASC&id_greater=2&limit=2'
    );
});

it('falls back to offset paging when the caller sets its own ordering', function () {
    Http::fakeSequence()
        ->push(contactPage(1, 2, 3))
        ->push(contactPage(3, 1, 3));

    ActiveCampaign::contacts()->all('orders[email]=ASC', perPage: 2);

    expect(urldecode(Http::recorded()[0][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/contacts?orders[email]=ASC&limit=2&offset=0')
        ->and(urldecode(Http::recorded()[1][0]->url()))
        ->toBe('https://test.api-us1.com/api/3/contacts?orders[email]=ASC&limit=2&offset=2');
});

it('falls back to offset paging when the caller sets an id bound', function () {
    Http::fakeSequence()->push(contactPage(6, 1, 1));

    ActiveCampaign::contacts()->all('id_greater=5', perPage: 2);

    expect(urldecode(sentUrl()))
        ->toBe('https://test.api-us1.com/api/3/contacts?id_greater=5&limit=2&offset=0');
});

it('stops instead of looping forever when the cursor cannot advance', function () {
    // A full page whose last record carries no usable id would otherwise repeat the same request.
    Http::fake(['test.api-us1.com/*' => Http::response([
        'contacts' => [['email' => 'a@example.com'], ['email' => 'b@example.com']],
        'meta' => ['total' => '99'],
    ])]);

    expect(ActiveCampaign::contacts()->lazy(perPage: 2)->count())->toBe(2);
    Http::assertSentCount(1);
});

it('still paginates contacts by offset with paginate()', function () {
    fakeActiveCampaign(contactPage(21, 20, 55));

    $paginator = ActiveCampaign::contacts()->paginate(perPage: 20, page: 2);

    expect($paginator->total())->toBe(55)
        ->and($paginator->lastPage())->toBe(3);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/contacts?limit=20&offset=20');
});

it('counts contacts from meta total', function () {
    fakeActiveCampaign(contactPage(1, 1, 4312));

    expect(ActiveCampaign::contacts()->count())->toBe(4312);
});
