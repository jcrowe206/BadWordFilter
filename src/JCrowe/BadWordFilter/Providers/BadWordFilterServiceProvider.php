<?php namespace JCrowe\BadWordFilter\Providers;

use Illuminate\Support\ServiceProvider;
use JCrowe\BadWordFilter\BadWordFilter;

class BadWordFilterServiceProvider extends ServiceProvider
{


    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;



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
            $config = $app->make('config');
            /** @var array $defaults */
            $defaults = $config->get('bad-word-filter');
            return new BadWordFilter($defaults);
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