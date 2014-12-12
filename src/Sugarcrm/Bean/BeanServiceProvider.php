<?php namespace Sugarcrm\Bean;

use Illuminate\Support\ServiceProvider;

class BeanServiceProvider extends ServiceProvider {

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
		$this->package('sugarcrm/laravel-bean');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
        /**
         * v10 Rest API
         */
        \App::singleton('SugarApi', function()
        {
            $api = new \Sugarcrm\Bean\Api\v10();
            $api->setUrl(\Config::get('laravel-bean::api.v10.url'))
                ->setUsername(\Config::get('laravel-bean::api.v10.user'))
                ->setPassword(\Config::get('laravel-bean::api.v10.password'));
            return $api;
        });
        /**
         * v4 Rest API
         */
        \App::singleton('SugarSoap', function()
        {
            $api = new \Sugarcrm\Bean\Api\v4();
            $api->setUrl(\Config::get('laravel-bean::api.v4.url'))
                ->setUsername(\Config::get('laravel-bean::api.v4.user'))
                ->setPassword(\Config::get('laravel-bean::api.v4.password'));
            return $api;
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
