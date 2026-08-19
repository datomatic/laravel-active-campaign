<?php

use Datomatic\ActiveCampaign\Tests\TestCase;
use Illuminate\Support\Facades\Http;

uses(TestCase::class)->in(__DIR__);

/**
 * @param  array<string, mixed>|callable  $response
 */
function fakeActiveCampaign(array|callable $response = [], int $status = 200): void
{
    Http::fake([
        'test.api-us1.com/*' => is_callable($response)
            ? $response
            : Http::response($response, $status),
    ]);
}

/**
 * @return array<string, mixed>
 */
function sentJson(): array
{
    $request = Http::recorded()[0][0];

    return json_decode($request->body() ?: '[]', true) ?: [];
}

function sentUrl(): string
{
    return Http::recorded()[0][0]->url();
}

function sentMethod(): string
{
    return Http::recorded()[0][0]->method();
}
