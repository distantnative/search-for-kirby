<?php

return [
    'index' => 'kirby',
    'collections' => [
        'pages' => $pages = site()->index(true)->filterBy('isReadable', true),
        'files' => $pages->files(),
        'users' => kirby()->users()
    ],
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
