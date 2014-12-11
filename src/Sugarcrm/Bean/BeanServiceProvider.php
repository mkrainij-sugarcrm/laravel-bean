<?php namespace Sugarcrm\Bean;

use Sugarcrm\Bean\Bean;
use Illuminate\Support\ServiceProvider;

class BeanServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Bean::setConnectionResolver($this->app['db']);
        Bean::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Add a sugarcrm extension to the original database manager
        $this->app['db']->extend('sugarcrm', function($config)
        {
            return new Connection($config);
        });
    }

}
