<?php

return [
    '/category/*' => [
        'controller' => 'Phire\Categories\Controller\IndexController',
        'action'     => 'index'
    ],
    APP_URI => [
        '/categories[/]' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'index'
            ]
        ],
        '/categories/add[/]' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'add'
            ]
        ],
        '/categories/edit/:id' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'edit'
            ]
        ],
        '/categories/view/:id' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'viewContent',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'view'
            ]
        ],
        '/categories/json/:id[/:type]' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'json',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'json'
            ]
        ],
        '/categories/process[/]' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'process',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'process'
            ]
        ],
        '/categories/remove[/]' => [
            'controller' => 'Phire\Categories\Controller\CategoryController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'categories',
                'permission' => 'remove'
            ]
        ]
    ]
];
