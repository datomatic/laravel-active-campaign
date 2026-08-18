<?php

// config for Datomatic/ActiveCampaign
return [
    /**
     * Your Active Campaign account URL, without the /api/3 suffix.
     * https://<your-account>.api-us1.com
     *
     * More information: https://developers.activecampaign.com/reference/url
     */
    'base_url' => env('ACTIVE_CAMPAIGN_BASE_URL'),

    /**
     * Your Active Campaign API key
     *
     * Your API key can be found in your account on the Settings page under the "Developer" tab.
     * Each user in your ActiveCampaign account has their own unique API key.
     */
    'api_key' => env('ACTIVE_CAMPAIGN_API_KEY'),

    /**
     * Request timeout, in seconds.
     */
    'timeout' => 100,

    /**
     * How many times a request is attempted before giving up.
     * Only connection errors, 429 and 5xx responses are retried.
     */
    'retry_times' => 3,

    /**
     * How long to wait between two attempts, in milliseconds.
     */
    'retry_sleep' => 1000,

    /**
     * (optional)
     * Map your Active Campaign custom field ids to the names you want to use in your code.
     */
    'custom_fields' => [
        // 'is_email_verified' => 50,
    ],
];
