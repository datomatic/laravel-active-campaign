<?php

namespace Datomatic\ActiveCampaign\Testing;

use Datomatic\ActiveCampaign\Enums\Method;
use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Fakes ActiveCampaign without making the caller hand-write base urls and response envelopes.
 *
 * Paths are given relative to /api/3, and may contain a `*` wildcard.
 */
class ActiveCampaignFake
{
    /**
     * Fake every ActiveCampaign call. Paths map to a response body; anything not listed
     * gets an empty 200, so a test only describes the calls it cares about.
     *
     * @param  array<string, array<string, mixed>|callable|PromiseInterface>  $responses
     */
    public static function fake(array $responses = []): void
    {
        $stubs = [];

        foreach ($responses as $path => $response) {
            $stub = is_array($response) ? Http::response($response) : $response;

            $stubs[self::url($path)] = $stub;

            // A path without a wildcard would otherwise miss its own query string,
            // which every paginated call carries.
            if (! str_contains($path, '*')) {
                $stubs[self::url($path).'?*'] = $stub;
            }
        }

        $stubs[self::url('*')] = Http::response([]);

        Http::fake($stubs);
    }

    /**
     * A list response, shaped the way the API returns collections.
     *
     * @param  string  $key  the envelope key, e.g. "contacts"
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public static function list(string $key, array $records, ?int $total = null): array
    {
        return [
            $key => array_values($records),
            'meta' => ['total' => strval($total ?? count($records))],
        ];
    }

    /**
     * A single-resource response, shaped the way the API returns one record.
     *
     * @param  string  $key  the envelope key, e.g. "contact"
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $sideloaded  extra top-level keys, e.g. fieldValues
     * @return array<string, mixed>
     */
    public static function single(string $key, array $record, array $sideloaded = []): array
    {
        return [$key => $record, ...$sideloaded];
    }

    /**
     * An error response, shaped the way the API reports failures.
     *
     * @param  array<int, string>  $titles
     */
    public static function error(array $titles, int $status = 422): PromiseInterface
    {
        return Http::response(
            ['errors' => array_map(fn (string $title): array => ['title' => $title], $titles)],
            $status,
        );
    }

    public static function assertSent(Method $method, string $path): void
    {
        Http::assertSent(fn (Request $request): bool => $request->method() === $method->value
            && self::matches($request->url(), $path));
    }

    public static function assertNotSent(Method $method, string $path): void
    {
        Http::assertNotSent(fn (Request $request): bool => $request->method() === $method->value
            && self::matches($request->url(), $path));
    }

    /**
     * Assert on the JSON body of the first request matching the method and path.
     *
     * @param  array<string, mixed>  $body
     */
    public static function assertSentJson(Method $method, string $path, array $body): void
    {
        Http::assertSent(fn (Request $request): bool => $request->method() === $method->value
            && self::matches($request->url(), $path)
            && $request->data() === $body);
    }

    public static function assertSentCount(int $count): void
    {
        Http::assertSentCount($count);
    }

    public static function assertNothingSent(): void
    {
        Http::assertNothingSent();
    }

    /**
     * The requests recorded so far, oldest first.
     *
     * @return array<int, Request>
     */
    public static function recorded(): array
    {
        return array_map(fn (array $pair): Request => $pair[0], Http::recorded()->all());
    }

    protected static function url(string $path): string
    {
        return ActiveCampaignConfig::baseUrl().'/api/3/'.ltrim($path, '/');
    }

    protected static function matches(string $url, string $path): bool
    {
        // The path may carry a query string we do not want to match on.
        return fnmatch(self::url($path), strtok($url, '?') ?: $url);
    }
}
