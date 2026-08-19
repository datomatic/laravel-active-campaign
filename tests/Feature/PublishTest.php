<?php

use Datomatic\ActiveCampaign\ActiveCampaignServiceProvider;

afterEach(fn () => @unlink(config_path('active-campaign.php')));

it('publishes the config file with the tag the readme documents', function () {
    $this->artisan('vendor:publish', ['--tag' => 'active-campaign-config'])->assertSuccessful();

    expect(config_path('active-campaign.php'))->toBeFile();
});

/**
 * spatie/laravel-package-tools builds the tag from the package short name, which strips the
 * "laravel-" prefix. Documenting the full package name instead silently publishes nothing.
 */
it('does not answer to the full package name as a tag', function () {
    $this->artisan('vendor:publish', ['--tag' => 'laravel-active-campaign-config']);

    expect(file_exists(config_path('active-campaign.php')))->toBeFalse();
});

it('registers the config under the key the package reads', function () {
    expect(config('active-campaign'))->toBeArray()
        ->and(app()->getProvider(ActiveCampaignServiceProvider::class))->not->toBeNull();
});
