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


# Configuration

Once you have laravel-bean installed you need to configure it so that it can work.

## Production configuration

Laravel-bean communicates with SugarCRM via the API rather than through a direct database connection.

### Metadata Cache

For your convinience we store metadata locally, so you will need to add new table to your system

```
php artisan migrate --package sugarcrm/laravel-bean
```


# Usage

Laravel's Query Builder lies in the root of Laravel-Bean therefore most of its fucntions and principals are true to the package. 

## Beans
As of this writing Laravel-Bean is not set up to be used as true Query Builder. You will have to set up models that extend Laravel-Bean in order to use query builder

SugarCRM User Modela will look something like that:

```
<?php namespace App\Models\Beans;

use Sugarcrm\Bean\Bean;

class UserBean extends Bean {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Users';


}

```

## Quering

Altimate goal of the Laravel-Bean was to bring filter and relationship API of SugarCRM as close to native Laravel Eloquent as possible

### Selects

Following our UserBean example, we can query Users Module

```

$bean = new UserBean();
$beans = $bean->get(); // get all records in the module. Please keep in mind that hardcoded limit is 1000 records.


```

You can set limit and offset 

```
$beans = $bean->take(10)->get(); // get first 10 records in the module

$beans = $bean->take(10)->skip(10)->get(); // get skip first 10 records and get next 10 records in the module

```

### Wheres

Due to the nature of SugarCRM's API not all typical database oprations are available. 

Below is a list of operation types:


#### $equals
Performs an exact match on that field.

```
$bean->where('name', 'something')->get();
```


#### $not_equals
Matches on non-matching values.

```
$bean->where('name', '!=', 'something')->get();
```

#### $starts
Matches on anything that starts with the value.

```
$bean->whereStartsWith('name', 'something')->get();
```
or

```
$bean->where('name', 'starts', 'something')->get();
```


#### $ends
Matches anything that ends with the value.

```
$bean->whereEndsWith('name', 'something')->get();
```
or

```
$bean->where('name', 'ends', 'something')->get();
```

#### $contains
Matches anything that contains the value

```
$bean->where('name', 'like', 'something')->get();
```


#### $in
Finds anything where field matches one of the values as specified as an array.


```
$bean->whereIn('name', array('Something','Else'))->get();
```

#### $not_in
Finds anything where field does not matches any of the values as specified as an array.

```
$bean->whereNotIn('name', array('Something','Else'))->get();
```


#### $is_null
Checks if the field is null. This operation does not need a value specified.

```
$bean->whereNull('name')->get();
```

#### $not_null
Checks if the field is not null. This operation does not need a value specified.

```
$bean->whereNotNull('name')->get();
```

#### $lt
Matches when the field is less than the value.

```
$bean->where('count', '<', 1)->get();
```


#### $lte
Matches when the field is less than or equal to the value.

```
$bean->where('count', '<=', 1)->get();
```

#### $gt
Matches when the field is greater than the value.

```
$bean->where('count', '>', 1)->get();
```

#### $gte
Matches when the field is greater than or equal to the value.

```
$bean->where('count', '>=', 1)->get();
```

### Inserts

### Updates

### Deletes