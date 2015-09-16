<?php
/**
 * Module Name: phire-categories
 * Author: Nick Sagona
 * Description: This is the categories module for Phire CMS 2, to be used in conjunction with the Content and Media modules
 * Version: 1.0
 */
return [
    'phire-categories' => [
        'prefix'     => 'Phire\Categories\\',
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
            'Phire\Categories\Model\Category' => []
        ],
        'events' => [
            [
                'name'     => 'app.route.pre',
                'action'   => 'Phire\Categories\Event\Category::bootstrap',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.pre',
                'action'   => 'Phire\Categories\Event\Category::setTemplate',
                'priority' => 1000
            ],
            [
                'name'   => 'app.send.pre',
                'action' => 'Phire\Categories\Event\Category::getAll',
                'priority' => 1000
            ],
            [
                'name'   => 'app.send.pre',
                'action' => 'Phire\Categories\Event\Category::save',
                'priority' => 1000
            ],
            [
                'name'   => 'app.send.pre',
                'action' => 'Phire\Categories\Event\Category::delete',
                'priority' => 1000
            ],
            [
                'name'   => 'app.send.pre',
                'action' => 'Phire\Categories\Event\Category::init',
                'priority' => 1000
            ],
            [
                'name'   => 'app.send.post',
                'action' => 'Phire\Categories\Event\Category::parseCategories',
                'priority' => 1000
            ]
        ],
        'settings' => [
            'content' => [
                'form' => [
                    'name'  => 'Phire\Content\Form\Content',
                    'group' => 0
                ],
                'model'    => 'Phire\Content\Model\Content',
                'method'   => 'getById',
                'required' => [
                    'status' => 1
                ],
                'order'    => 'publish DESC',
                'remove'   => 'process_content'
            ],
            'media' => [
                'form' => [
                    'name'  => 'Phire\Media\Form\Media',
                    'group' => 0
                ],
                'model'  => 'Phire\Media\Model\Media',
                'method' => 'getById',
                'order'  => 'uploaded DESC',
                'remove' => 'rm_media'
            ]
        ],
        'separator'       => ' &gt; ',
        'summary_length'  => 150,
        'date_fields'     => [
            'publish', 'uploaded'
        ],
        'show_total'      => true,
        'nav_config'      => [
            'top'    => [
                'node' => 'ul',
                'id'   => 'category-nav'
            ],
            'parent' => [
                'node' => 'ul'
            ],
            'child' => [
                'node' => 'li'
            ]
        ]
    ]
];
