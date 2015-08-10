<?php

return [
    APP_URI => [
        '/categories[/]' => [
            'controller' => 'Categories\Controller\IndexController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'index'
            ]
        ],
        '/categories/add[/]' => [
            'controller' => 'Categories\Controller\IndexController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'add'
            ]
        ],
        '/categories/edit/:id' => [
            'controller' => 'Categories\Controller\IndexController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'edit'
            ]
        ],
        '/categories/json/:id' => [
            'controller' => 'Categories\Controller\IndexController',
            'action'     => 'json',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'json'
            ]
        ],
        '/categories/remove[/]' => [
            'controller' => 'Categories\Controller\IndexController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'remove'
            ]
        ]
    ]
];
