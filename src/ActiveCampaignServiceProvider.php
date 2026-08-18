<?php

namespace Datomatic\ActiveCampaign;

use Datomatic\ActiveCampaign\Contracts\ActiveCampaignClientContract;
use Datomatic\ActiveCampaign\Factories\ActiveCampaignClientFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActiveCampaignServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-active-campaign')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        // Bound (not shared) on purpose: a PendingRequest is stateful and must not be reused across calls.
        $this->app->bind(ActiveCampaignClientContract::class, fn () => ActiveCampaignClientFactory::make());
        $this->app->singleton(ActiveCampaign::class);
    }
}
