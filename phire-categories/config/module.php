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
                'action'   => 'Phire\Categories\Event\Category::init',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.pre',
                'action'   => 'Phire\Categories\Event\Category::setTemplate',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.pre',
                'action'   => 'Phire\Categories\Event\Category::getAll',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.pre',
                'action'   => 'Phire\Categories\Event\Category::save',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.post',
                'action'   => 'Phire\Categories\Event\Category::parseCategories',
                'priority' => 1000
            ]
        ],
        'date_format'    => 'n/j/Y',
        'month_format'   => 'M',
        'day_format'     => 'j',
        'year_format'    => 'Y',
        'time_format'    => 'H:i',
        'hour_format'    => 'H',
        'minute_format'  => 'i',
        'period_format'  => 'A',
        'separator'      => '&gt;',
        'filters'        => [
            'strip_tags' => null,
            'substr'     => [0, 150]
        ],
        'show_total'      => true,
        'nav_config'      => [
            'top'    => [
                'node'  => 'ul',
                'id'    => 'category-nav',
                'class' => 'category-nav'
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
