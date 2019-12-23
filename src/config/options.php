<?php

return [
    'limit' => 10,
    'collections' => [
        'pages' => $pages = site()->index(true)->filterBy('isReadable', true),
        'files' => $pages->files(),
        'users' => kirby()->users()
    ],
    'fields' => [
        'pages' => [
            'title'
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
    'fuse'=> [
        'minMatchCharLength' => 2,
        'threshold'          => 0.4,
        'distance'           => 60,
        'findAllMatches'     => true
    ]
];
