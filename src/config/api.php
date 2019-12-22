<?php

return [
    'routes' => [
        [
            'pattern' => 'algolia',
            'method'  => 'GET',
            'action'  => function () {
                return algolia(
                    $this->requestQuery('q'),
                    [],
                    $this->requestQuery('page') ?? 1
                );
            }
        ],
        [
            'pattern' => 'algolia',
            'method'  => 'POST',
            'action'  => function () {
                Kirby\Algolia\Index::instance()->build();
                return true;
            }
        ]
    ],
    'collections' => [
        'results' => [
            'type'  => 'Kirby\Algolia\Results'
        ]
    ]
];
