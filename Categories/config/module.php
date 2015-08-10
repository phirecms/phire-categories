<?php
/**
 * Module Name: Categories
 * Author: Nick Sagona
 * Description: This is the categories module for Phire CMS 2, to be used in conjunction with the Content and Media modules
 * Version: 1.0
 */
return [
    'Categories' => [
        'prefix'     => 'Categories\\',
        'src'        => __DIR__ . '/../src',
        'routes'     => include 'routes.php',
        'resources'  => include 'resources.php',
        'forms'      => include 'forms.php',
        'nav.phire'  => [
            'categories' => [
                'name' => 'Categories',
                'href' => '/categories',
                'acl' => [
                    'resource'   => 'categories',
                    'permission' => 'index'
                ],
                'attributes' => [
                    'class' => 'categories-nav-icon'
                ]
            ]
        ],
        'models' => [
            'Categories\Model\Category' => []
        ],
        'events' => [
            [
                'name'     => 'app.route.pre',
                'action'   => 'Categories\Event\Category::bootstrap',
                'priority' => 1000
            ]
        ]
    ]
];
