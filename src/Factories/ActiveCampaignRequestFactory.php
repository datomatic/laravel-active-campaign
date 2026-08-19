<?php

namespace Datomatic\ActiveCampaign\Factories;

use Datomatic\ActiveCampaign\Support\ActiveCampaignConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ActiveCampaignRequestFactory
{
    public static function make(): PendingRequest
    {
        return Http::withHeaders([
            'Api-Token' => ActiveCampaignConfig::apiKey(),
        ])
            ->acceptJson()
            ->baseUrl(ActiveCampaignConfig::baseUrl().'/api/3')
            ->timeout(ActiveCampaignConfig::timeout())
            ->retry(
                times: ActiveCampaignConfig::retryTimes(),
                sleepMilliseconds: ActiveCampaignConfig::retrySleep(),
                when: self::shouldRetry(...),
                throw: false,
            );
    }

    /**
     * Only network failures, rate limiting and server errors are worth a new attempt:
     * retrying a 4xx would just burn the configured attempts on a request that can't succeed.
     */
    protected static function shouldRetry(\Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        return $exception->response->status() === 429 || $exception->response->serverError();
    }
}
