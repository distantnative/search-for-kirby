<?php

return [
    'routes' => [
        [
            'pattern' => 'search',
            'method'  => 'GET',
            'action'  => function () {
                return search(
                    $this->requestQuery('q'),
                    ['page' => $this->requestQuery('page') ?? 1]
                );
            }
        ],
        [
            'pattern' => 'search',
            'method'  => 'POST',
            'action'  => function () {
                Kirby\Search\Index::instance()->build();
                return true;
            }
        ]
    ],
    'collections' => [
        'results' => [
            'type'  => 'Kirby\Search\Results'
        ]
    ]
];
