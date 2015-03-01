<?php

return [

    /**
     *
     * This is an example config for laravel-bean
     * Copy array bellow to your database.php
     * configuration file
     *
     */
    'connections' => [

        // ....

        'sugarcrm' => [
            'driver'    => 'sugarcrm',
            'host'      => env('LARAVEL_BEAN_HOST'),
            'username'  => env('LARAVEL_BEAN_USERNAME'),
            'password'  => env('LARAVEL_BEAN_PASSWORD'),
            'client_id' => env('LARAVEL_BEAN_CLIENT_ID'),
            "platform"  => env('LARAVEL_BEAN_PLATFORM'),
        ],
    ],

];