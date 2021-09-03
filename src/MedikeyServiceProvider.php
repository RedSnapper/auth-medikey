<?php

namespace RedSnapper\Medikey;

use Illuminate\Support\ServiceProvider;

class MedikeyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     */
    public function register()
    {

        $this->app->singleton(MedikeyProvider::class, function ($app) {
            return new MedikeyProvider($app->make('request'), config('services.medikey.site_id'));
        });
    }
}
