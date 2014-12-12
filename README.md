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

Laravel-bean communicates with SugarCRM via the API rather than through a direct database connection.  We find this to be cleaner and safer in general.  For that to work you must first configure laravel-bean to work with your laravel instance.  

To do so, go to the top directory of your laravel instance and issue the command 
```
php artisan config:publish sugarcrm/laravel-bean
```
The new file `app/config/packages/sugarcrm/laravel-bean/config.php` will appear in your instance.  When you edit the file you'll see:
```
<?php

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
            'url'      => '',
            'user'     => '',
            'password' => '',
        ),
        'v10' => array(
            'url'      => '',
            'user'     => '',
            'password' => '',
        )
    ),
);
```
This file is the configuration for the production instance of your application.  The comments should explain what values to fill in.  Right now laravel-bean uses both the v4 and v10 APIs for SugarCRM.  Soon it will only use v10.

## Additional configurations

The laravel-bean package also supports separate configurations for "local", "dev", and "stage" instances of SugarCRM.  You can configure any, all, or none of them if you wish.  Once you have configured the production configuration above, all you need to do is create a file `app/config/packages/sugarcrm/bean/{instance type}/config.php` with exactly the same format.  For example, if you wanted to set up a staging instance you would create the file `app/config/packages/sugarcrm/bean/stage/config.php`.

So, how does laravel know which configuration to use on any given machine?  You use the `detectEnvironment()` method in `bootstrap/start.php` as described in [the Laravel documentation](http://laravel.com/docs/4.2/configuration#environment-configuration).  As an example you could have the following in `bootstrap/start.php` :
```
<?php
...
$env = $app->detectEnvironment(
    array(
        'dev'   => array(
            'devel-*.ourcompany.com',
            'alternattestingbox.ourcompany.com',
        ),
        'stage' => array('ouronlystagingserver.ourcompany.com'),
        'local' => array(
            'joeslocalpc.ourcompany.com',
            '*testdev*',
        ),
    )
);
```
Once you have that in place Laravel will set the environment for you based on what machine you're on.  It's important to realize that if you're on any machine not matching any of those conditions Laravel will assume you're in production.

# Usage

The laravel-bean package provides for a `Bean` object which corresponds to a SugarBean in SugarCRM.  Generally you would extend Bean rather than use it directly.  For example, to represent an Opportunity you might create a class such as: 
```
class OpportunityBean extends \Sugarcrm\Bean\Bean {
...
}
```
You can then instantiate an Opportunity bean and use it's methods.  The methods available for the Bean object mimic those available for SugarBean in SugarCRM.  Here are some examples.  Take a look at `vendor/sugarcrm/laravel-bean/src/Sugarcrm/Bean/Bean.php` to get a more complete picture:
```
$opp = new OpportunityBean();
...
// Get all opportunities in the system (you probably shouldn't ever do this)
$all_opportunities = $opp->all();
...
// Delete an opportunity
$opp->delete("abc123-576475-kju838-dkie83");
...
// If you want to delete using a static call
Bean::deleteBean("abc123-576475-kju838-dkie83");
...
// Get certain columns of the first 100 opportunities matching a certain where clause
$columns = array("id","name);
$options = array("limit" => 100, "where" => $where_clause);
$my_collection = $opp->get($columns, $options);
...
// Get a particular bean
class OpportunityBean extends \Sugarcrm\Bean\Bean {
   public $module = "Opportunities";
}
$another_opp = OpportunityBean::find("abc123-576475-kju838-dkie83");
...
// Relate one bean to another
$contact = ContactBean::find("abc123-576475-kju838-dkie83");
$opportunity = OpportunityBean::find("xxyyyzz-123432-l3fl34l-d0d76hgjkeuyd");
$opportunity->relate("contacts", "abc123-576475-kju838-dkie83");
// Unrelate one bean from another
$opportunity->unrelate("contacts", "abc123-576475-kju838-dkie83");
...
```


