<?php

use Datomatic\ActiveCampaign\Enums\BulkImportStatus;
use Datomatic\ActiveCampaign\Exceptions\ActiveCampaignException;
use Datomatic\ActiveCampaign\Facades\ActiveCampaign;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignImportResource;
use Datomatic\ActiveCampaign\Testing\ActiveCampaignFake;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

function queued(int $count = 1, string $batchId = 'batch-1'): array
{
    return [
        'Success' => 1,
        'queued_contacts' => $count,
        'batchId' => $batchId,
        'message' => 'Contact import queued',
    ];
}

it('queues a batch of contacts', function () {
    fakeActiveCampaign(queued(2));

    $result = ActiveCampaign::import()->bulk([
        ['email' => 'a@example.com'],
        ['email' => 'b@example.com'],
    ]);

    expect($result['batchId'])->toBe('batch-1');
    expect(sentMethod())->toBe('POST')
        ->and(sentUrl())->toBe('https://test.api-us1.com/api/3/import/bulk_import')
        ->and(sentJson())->toBe(['contacts' => [
            ['email' => 'a@example.com'],
            ['email' => 'b@example.com'],
        ]]);
});

it('translates our contact shape to the importer shape', function () {
    config()->set('active-campaign.custom_fields', ['city' => 50, 'plan' => 51]);
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([[
        'email' => 'a@example.com',
        'firstName' => 'Jane',
        'lastName' => 'Doe',
        'phone' => '123',
        'city' => 'Rome',
        'plan' => '',
        'unknownKey' => 'dropped',
    ]]);

    expect(sentJson()['contacts'][0])->toBe([
        'email' => 'a@example.com',
        'phone' => '123',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'fields' => [['id' => 50, 'value' => 'Rome']],
    ]);
});

it('passes the importer own keys through untouched', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([[
        'email' => 'a@example.com',
        'first_name' => 'Jane',
        'tags' => ['customer'],
        'fields' => [['id' => 9, 'value' => 'x']],
        'customer_acct_name' => 'ActiveCampaign',
    ]]);

    expect(sentJson()['contacts'][0])->toBe([
        'email' => 'a@example.com',
        'first_name' => 'Jane',
        'tags' => ['customer'],
        'fields' => [['id' => 9, 'value' => 'x']],
        'customer_acct_name' => 'ActiveCampaign',
    ]);
});

it('prefers an explicit snake case name over ours', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([[
        'email' => 'a@example.com',
        'firstName' => 'Ours',
        'first_name' => 'Theirs',
    ]]);

    expect(sentJson()['contacts'][0]['first_name'])->toBe('Theirs');
});

it('merges config custom fields into explicit fields', function () {
    config()->set('active-campaign.custom_fields', ['city' => 50]);
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([[
        'email' => 'a@example.com',
        'fields' => [['id' => 9, 'value' => 'x']],
        'city' => 'Rome',
    ]]);

    expect(sentJson()['contacts'][0]['fields'])->toBe([
        ['id' => 9, 'value' => 'x'],
        ['id' => 50, 'value' => 'Rome'],
    ]);
});

it('accepts list ids as plain integers', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([[
        'email' => 'a@example.com',
        'subscribe' => [1, 2],
        'unsubscribe' => [['listid' => 3]],
    ]]);

    expect(sentJson()['contacts'][0]['subscribe'])->toBe([['listid' => 1], ['listid' => 2]])
        ->and(sentJson()['contacts'][0]['unsubscribe'])->toBe([['listid' => 3]]);
});

it('sends a callback when given one', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk(
        [['email' => 'a@example.com']],
        callback: ['url' => 'https://example.com/hook', 'requestType' => 'POST'],
    );

    expect(sentJson()['callback'])->toBe(['url' => 'https://example.com/hook', 'requestType' => 'POST']);
});

it('omits the callback key when none is given', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([['email' => 'a@example.com']]);

    expect(sentJson())->not->toHaveKey('callback');
});

it('refuses a contact without an email', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulk([['firstName' => 'Jane']]);
})->throws(ActiveCampaignException::class, 'Missing required field "email" on import/bulk_import request');

it('refuses a batch above the api maximum', function () {
    fakeActiveCampaign(queued());

    $contacts = collect(range(1, 251))->map(fn (int $i) => ['email' => 'u'.$i.'@example.com'])->all();

    ActiveCampaign::import()->bulk($contacts);
})->throws(ActiveCampaignException::class, 'A bulk import accepts at most 250 contacts per request, 251 given');

it('splits a large import into batches the api accepts', function () {
    fakeActiveCampaign(queued());

    $contacts = collect(range(1, 600))->map(fn (int $i) => ['email' => 'u'.$i.'@example.com'])->all();

    $batches = ActiveCampaign::import()->bulkAll($contacts);

    expect($batches)->toHaveCount(3);
    Http::assertSentCount(3);

    $sizes = collect(Http::recorded())
        ->map(fn (array $pair) => count(json_decode($pair[0]->body(), true)['contacts']))
        ->all();

    expect($sizes)->toBe([250, 250, 100]);
});

it('honours a smaller batch size', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulkAll(
        collect(range(1, 5))->map(fn (int $i) => ['email' => 'u'.$i.'@example.com'])->all(),
        batchSize: 2,
    );

    Http::assertSentCount(3);
});

it('clamps a batch size above the maximum', function () {
    fakeActiveCampaign(queued());

    ActiveCampaign::import()->bulkAll([['email' => 'a@example.com']], batchSize: 9999);

    expect(sentJson()['contacts'])->toHaveCount(1);
});

it('accepts a lazy collection so a large import never lands in memory', function () {
    fakeActiveCampaign(queued());

    $contacts = LazyCollection::make(function () {
        foreach (range(1, 300) as $i) {
            yield ['email' => 'u'.$i.'@example.com'];
        }
    });

    expect(ActiveCampaign::import()->bulkAll($contacts))->toHaveCount(2);
});

it('reads a batch status', function () {
    fakeActiveCampaign([
        'status' => 'completed',
        'success' => ['123', '124'],
        'failure' => ['bad@invalid'],
    ]);

    $status = ActiveCampaign::import()->status('batch-1');

    expect($status['success'])->toBe(['123', '124'])
        ->and($status['failure'])->toBe(['bad@invalid']);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/import/info?batchId=batch-1');
});

it('casts a batch status to an enum', function () {
    fakeActiveCampaign(['status' => 'active']);

    expect(ActiveCampaign::import()->statusOf('batch-1'))->toBe(BulkImportStatus::Active);
});

it('returns no status while the api has not set one', function () {
    fakeActiveCampaign(['status' => false]);

    expect(ActiveCampaign::import()->statusOf('batch-1'))->toBeNull();
});

it('returns no status for a value it does not know', function () {
    fakeActiveCampaign(['status' => 'something-new']);

    expect(ActiveCampaign::import()->statusOf('batch-1'))->toBeNull();
});

it('knows which statuses are final', function (BulkImportStatus $status, bool $finished) {
    expect($status->isFinished())->toBe($finished);
})->with([
    [BulkImportStatus::Waiting, false],
    [BulkImportStatus::Claimed, false],
    [BulkImportStatus::Active, false],
    [BulkImportStatus::Completed, true],
    [BulkImportStatus::Failed, true],
    [BulkImportStatus::Interrupted, true],
]);

it('reads account wide import info', function () {
    fakeActiveCampaign([
        'outstanding' => [['forDate' => '2021-06-01', 'batches' => '333', 'contacts' => '83250']],
        'recentlyCompleted' => [],
    ]);

    expect(ActiveCampaign::import()->info()['outstanding'])->toHaveCount(1);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/import/bulk_import');
});

it('surfaces an import error as an exception', function () {
    ActiveCampaignFake::fake([
        'import/bulk_import' => ActiveCampaignFake::error(['Invalid batch'], 422),
    ]);

    ActiveCampaign::import()->bulk([['email' => 'a@example.com']]);
})->throws(ActiveCampaignException::class, 'Invalid batch');

it('offers no crud surface', function (string $method) {
    expect(method_exists(ActiveCampaignImportResource::class, $method))->toBeFalse();
})->with(['list', 'get', 'create', 'update', 'delete', 'paginate', 'lazy', 'all', 'count']);

it('resolves the import resource', function () {
    expect(resolve(Datomatic\ActiveCampaign\ActiveCampaign::class)->import())
        ->toBeInstanceOf(ActiveCampaignImportResource::class);
});
