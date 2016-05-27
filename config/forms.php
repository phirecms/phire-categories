<?php
/**
 * phire-categories form configuration
 */
return [
    'Phire\Categories\Form\Category' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'category_parent_id' => [
                'type'  => 'select',
                'label' => 'Parent',
                'value' => [
                    '----' => '----',
                ],
                'attributes' => [
                    'onchange' => 'phire.changeCategoryUri();'
                ]
            ],
            'order_by_field' => [
                'type'  => 'select',
                'label' => 'Order By',
                'value' => [
                    DB_PREFIX . 'category_items.order' => 'order',
                    DB_PREFIX . 'content.id'           => 'content.id',
                    DB_PREFIX . 'content.title'        => 'content.title',
                    DB_PREFIX . 'content.publish'      => 'content.publish',
                    DB_PREFIX . 'content.expire'       => 'content.expire',
                    DB_PREFIX . 'content.created'      => 'content.created',
                    DB_PREFIX . 'content.updated'      => 'content.updated',
                    DB_PREFIX . 'content.order'        => 'content.order',
                    DB_PREFIX . 'media.id'             => 'media.id',
                    DB_PREFIX . 'media.title'          => 'media.title',
                    DB_PREFIX . 'media.uploaded'       => 'media.uploaded',
                    DB_PREFIX . 'media.order'          => 'media.order'
                ]
            ],
            'order_by_order' => [
                'type'  => 'select',
                'value' => [
                    'ASC'  => 'ASC',
                    'DESC' => 'DESC'
                ]
            ],
            'order' => [
                'type'  => 'text',
                'label' => 'Category Order',
                'value' => 0,
                'attributes' => [
                    'size'  => 2,
                    'class' => 'order-field'
                ]
            ],
            'pagination' => [
                'type'  => 'text',
                'label' => 'Pagination',
                'value' => 25,
                'attributes' => [
                    'size'  => 2,
                    'class' => 'order-field'
                ]
            ],
            'filter' => [
                'type'  => 'radio',
                'label' => 'Filter',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 1
            ],
            'id' => [
                'type'  => 'hidden',
                'value' => 0
            ]
        ],
        [
            'title' => [
                'type'       => 'text',
                'label'      => 'Title',
                'required'   => true,
                'attributes' => [
                    'size'   => 60,
                    'style'  => 'width: 99.5%'
                ]
            ],
            'slug' => [
                'type'       => 'text',
                'label'      => 'URI',
                'attributes' => [
                    'size'     => 60,
                    'style'    => 'width: 99.5%'
                ]
            ],
            'uri' => [
                'type'  => 'hidden',
                'label' => '<span id="uri-span"></span>',
                'value' => ''
            ]
        ]
    ]
];
