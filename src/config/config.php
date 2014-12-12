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
    'drivers'=>array(
        'metadata' => 'Sugarcrm\\Bean\\Cache\\FieldMetadata',
    ),
);