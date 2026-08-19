<?php

use Datomatic\ActiveCampaign\ActiveCampaign;
use Datomatic\ActiveCampaign\ActiveCampaignClient;
use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignContactsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldsResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignFieldValuesResource;
use Datomatic\ActiveCampaign\Resources\ActiveCampaignTagsResource;

it('binds the client contract', function () {
    expect(resolve(ActiveCampaignClientContract::class))->toBeInstanceOf(ActiveCampaignClient::class);
});

it('builds a fresh client on every resolution', function () {
    expect(resolve(ActiveCampaignClientContract::class))
        ->not->toBe(resolve(ActiveCampaignClientContract::class));
});

it('exposes every resource', function (string $method, string $class) {
    expect(resolve(ActiveCampaign::class)->{$method}())->toBeInstanceOf($class);
})->with([
    ['contacts', ActiveCampaignContactsResource::class],
    ['fields', ActiveCampaignFieldsResource::class],
    ['fieldValues', ActiveCampaignFieldValuesResource::class],
    ['tags', ActiveCampaignTagsResource::class],
]);

it('ships a config file with every param the package reads', function () {
    $config = require __DIR__.'/../../config/active-campaign.php';

    expect($config)->toHaveKeys([
        'base_url',
        'api_key',
        'timeout',
        'retry_times',
        'retry_sleep',
        'custom_fields',
    ]);
});

it('exposes request() as an escape hatch for unwrapped endpoints', function () {
    fakeActiveCampaign(['lists' => [['id' => '1', 'name' => 'Newsletter']]]);

    $lists = resolve(ActiveCampaign::class)->contacts()->request(
        method: Method::GET,
        path: 'lists',
        responseKey: 'lists',
    );

    expect($lists)->toBe([['id' => '1', 'name' => 'Newsletter']]);
    expect(sentUrl())->toBe('https://test.api-us1.com/api/3/lists');
});

/**
 * The facade's only contract with an IDE is its @method docblock, and nothing enforces it,
 * so a resource added to the manager can silently stay invisible to autocompletion.
 */
it('documents every manager method on the facade', function () {
    $manager = collect((new ReflectionClass(ActiveCampaign::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $method) => $method->isStatic() || $method->isConstructor())
        ->map(fn (ReflectionMethod $method) => $method->getName())
        ->sort()->values();

    $docblock = (new ReflectionClass(Datomatic\ActiveCampaign\Facades\ActiveCampaign::class))->getDocComment();

    preg_match_all('/@method static \S+ (\w+)\(/', (string) $docblock, $matches);

    expect(collect($matches[1])->sort()->values()->all())->toBe($manager->all());
});

it('returns the documented resource class for every manager method', function () {
    $manager = resolve(ActiveCampaign::class);

    foreach ((new ReflectionClass(ActiveCampaign::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isConstructor()) {
            continue;
        }

        $returnType = (string) $method->getReturnType();

        expect($manager->{$method->getName()}())->toBeInstanceOf($returnType);
    }
});
