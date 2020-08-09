<?php


namespace ApolloClient;


class ApolloClientServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('apollo.php'),
        ], 'apollo');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton(ApolloClient::class, function () {
            return new ApolloClient(config('apollo.base_url'), config('apollo.app_id'), config('apollo.namespace'));
        });

        $this->app->alias(ApolloClient::class, 'apollo_client');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ApolloClient::class, 'apollo_client'];
    }
}