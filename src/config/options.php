<?php

return [
    'index' => 'kirby',
    'fields' => [
        'pages' => [
            'title',
            'text'
        ],
        'files' => [
            'filename'
        ],
        'users' => [
            'email',
            'name'
        ]
    ],
    'templates' => [
        'pages' => function ($model) {
            return $model->id() !== 'home' && $model->id() !== 'error';
        }
    ],
    'hooks' => true,
    'options' => [

    ]
];
