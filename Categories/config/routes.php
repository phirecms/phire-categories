<?php

return [
    '/category/*' => [
        'controller' => 'Categories\Controller\IndexController',
        'action'     => 'index'
    ],
    APP_URI => [
        '/categories[/]' => [
            'controller' => 'Categories\Controller\CategoryController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'index'
            ]
        ],
        '/categories/add[/]' => [
            'controller' => 'Categories\Controller\CategoryController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'add'
            ]
        ],
        '/categories/edit/:id' => [
            'controller' => 'Categories\Controller\CategoryController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'edit'
            ]
        ],
        '/categories/json/:id' => [
            'controller' => 'Categories\Controller\CategoryController',
            'action'     => 'json',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'json'
            ]
        ],
        '/categories/remove[/]' => [
            'controller' => 'Categories\Controller\CategoryController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'remove'
            ]
        ]
    ]
];
