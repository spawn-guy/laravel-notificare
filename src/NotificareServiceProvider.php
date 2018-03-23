<?php

namespace Notificare\Notificare;

use Illuminate\Support\ServiceProvider;

class NotificareServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/notificare.php';

        $this->publishes([$configPath => config_path('notificare.php')], 'config');
        $this->mergeConfigFrom($configPath, 'notificare');

        if (class_exists('Laravel\Lumen\Application')) {
            $this->app->configure('notificare');
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('notificare', function ($app) {
            $config = isset($app['config']['services']['notificare']) ? $app['config']['services']['notificare'] : null;
            if (is_null($config)) {
                $config = $app['config']['notificare'] ?: $app['config']['notificare::config'];
            }

            $client = new NotificareClient($config);

            return $client;
        });
    }

    public function provides()
    {
        return ['notificare'];
    }


}
