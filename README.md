laravel-bean
============

SugarCRM Bean infrastructure for Laravel

Use this to get easy access to SugarCRM beans from your Laravel application

============
**THIS PACKAGE IS CURRENTLY IN AN ALPHA STATE AND IS NOT SUITABLE FOR PRODUCTION USE**
============

Here are some basic directions to get you going with this package.

# Installation

This package is meant to be installed via [Composer](https://getcomposer.org/).  These directions assume you have composer installed and working.

In the root directory of your laravel application you should have a `composer.json` file.  Within that file, add a line for laravel-bean to the `require` section.  As an example (Here `...` indicates other stuff in your composer.json file which may exist):
```
{
	"name": "laravel/laravel",
	"description": "The Laravel Framework.",
	"keywords": ["framework", "laravel"],
	"license": "MIT",
	...
	"require": {
		"laravel/framework": "4.1.31",
		...
    "sugarcrm/laravel-bean": "1.2.3",
    ...
	},
...
}
```
You should substitute the "1.2.3" with whatever latest production version of the package is available.  Once you've done that you can run `composer update` and it should install laravel-bean for you.  It should appear under `vendor/sugarcrm/laravel-bean`.

# Configuration

Once you have laravel-bean installed you need to configure it so that it can work.

## Production configuration

Laravel-bean communicates with SugarCRM via the API rather than through a direct database connection.  We find this to be cleaner and safer in general.  For that to work you need to configure a single file:  `app/config/packages/sugarcrm/bean/config.php`:
```
return array(
    /**
     * Populate the "api" array as follows:
     * 'api' => array(
     *     'v4'  => array(
     *         'url'      => '{full URL to the v4 REST API in SugarCRM}',
     *         'user'     => '{username with which to attach to the v4 API}',
     *         'password' => '{password that goes with the username}',
     *     ),
     *     'v10' => array(
     *         'url'      => '{full URL to the v10 REST API in SugarCRM}',
     *         'user'     => '{username with which to attach to the v10 API}',
     *         'password' => '{password that goes with the username Note:  Not MD5 hashed}',
     *     )
     * ),
     */

    'api' => array(
        'v4'  => array(
            'url'      => 'https://mysugarserver.com/service/v4_1/rest.php',
            'user'     => 'myv4username',
            'password' => 'myv4userpassword',
        ),
        'v10' => array(
            'url'      => 'https://mysugarserver.com/rest/v10',
            'user'     => 'myv10username',
            'password' => 'myv10userpassword',
        )
    ),
);
```
Right now laravel-bean uses both the v4 and v10 APIs.  Soon it will use only the v10 API.

You need to provide the URL, user, and password necessary to connect with the APIs.  Note that the password for v4 will be MD5 hashed, but the password for v10 won't be.

This will serve as the production configuration for laravel-bean.

## Additional configurations

The laravel-bean package also supports separate configurations for "local", "dev", and "stage" instances of SugarCRM.  You can configure any, all, or none of them if you wish.  All you need to do is create a directory under `app/config/packages/sugarcrm/bean/{instance type}` and create a `config.php` file in that directory with exactly the same format.  If you wanted to set up a staging instance you would create the file `app/config/packages/sugarcrm/bean/stage/config.php`.

So, how does laravel know which configuration to use?  You use the detectEnvironment() method in `bootstrap/start.php` as described in [the Laravel documentation](http://laravel.com/docs/4.2/configuration#environment-configuration).  As an example you could have the following in `bootstrap/start.php`:
```
<?php
...
$env = $app->detectEnvironment(
    array(
        'dev'   => array(
            'devel-*.ourcompany.com',
        ),
        'stage' => array('ouronlystagingserver.ourcompany.com'),
        'local' => array(
            'joeslocalpc.ourcompany.com',
            '*testdev*',
        ),
    )
);
```
Once you have that in place Laravel will set the environment for you based on what machine you're on.

# Usage
