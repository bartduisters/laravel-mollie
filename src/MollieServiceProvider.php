<?php
/**
 * Copyright (c) 2016, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @license     Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @author      Mollie B.V. <info@mollie.com>
 * @copyright   Mollie B.V.
 * @link        https://www.mollie.com
 */
namespace Mollie\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

/**
 * Class MollieServiceProvider.
 */
class MollieServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->setupConfig();
        $this->extendSocialite();
    }

    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/mollie.php');

        // Check if the application is a Laravel OR Lumen instance to properly merge the configuration file.
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('mollie.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('mollie');
        }

        $this->mergeConfigFrom($source, 'mollie');
    }

    /**
     * Extend the Laravel Socialite factory class.
     */
    protected function extendSocialite()
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');

        $socialite->extend('mollie', function (Container $app) use ($socialite) {
            $config = $app['config']['services.mollie'];

            return $socialite->buildProvider(MollieConnectProvider::class, $config);
        });
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerApiAdapter();
        $this->registerManager();
    }

    /**
     * Register the Mollie API adapter class.
     */
    protected function registerApiAdapter()
    {
        $this->app->singleton('mollie.api', function (Container $app) {
            $config = $app['config'];

            return new MollieApiWrapper($config);
        });

        $this->app->alias('mollie.api', MollieApiWrapper::class);
    }

    /**
     * Register the manager class.
     */
    public function registerManager()
    {
        $this->app->singleton('mollie', function (Container $app) {
            return new MollieManager($app);
        });

        $this->app->alias('mollie', MollieManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mollie.api',
            'mollie',
        ];
    }
}
