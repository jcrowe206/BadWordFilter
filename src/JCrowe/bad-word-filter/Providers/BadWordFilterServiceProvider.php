<?php namespace JCrowe\BadWordFilter\Providers;


class BadWordFilterServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $namespace = 'bad-word-filter';
        $path = __DIR__ . '/../../..';
        $this->package('jcrowe/bad-word-filter', $namespace, $path);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('bad-word-filter', function($app) {
            return new BadWordFilter($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('bad-word-filter');
    }

}