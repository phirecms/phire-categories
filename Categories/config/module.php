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
            ],
            [
                'name'   => 'app.send',
                'action' => 'Categories\Event\Category::getAll'
            ],
            [
                'name'   => 'app.send',
                'action' => 'Categories\Event\Category::save'
            ],
            [
                'name'   => 'app.send',
                'action' => 'Categories\Event\Category::delete'
            ]
        ],
        'categories' => [
            'content' => [
                'form' => [
                    'name'  => 'Content\Form\Content',
                    'group' => 0
                ],
                'remove' => 'process_content'
            ],
            'media' => [
                'form' => [
                    'name'  => 'Media\Form\Media',
                    'group' => 0
                ],
                'remove' => 'rm_media'
            ]
        ]
    ]
];
