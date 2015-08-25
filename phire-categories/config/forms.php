<?php

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
            'order' => [
                'type'  => 'text',
                'label' => 'Order',
                'value' => 0,
                'attributes' => [
                    'size'  => 2,
                    'class' => 'order-field'
                ]
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
