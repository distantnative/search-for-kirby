<?php

use Kirby\Cms\App;
use Kirby\Cms\Collection;
use Kirby\Algolia\Search;

include __DIR__ . '/vendor/autoload.php';

App::plugin('getkirby/algolia', [
    'api'   => require 'src/config/api.php',
    'hooks' => require 'src/config/hooks.php',
    'translations' => [
        'en' => require 'src/config/i18n/en.php'
    ],
    'sections' => [
        'algolia' => []
    ],
    'components' => [
        'search' => function (App $kirby, Collection $collection, string $query = null, $params = []) {
            return Search::collection($collection, $query, $params);
        }
    ]
]);

function algolia(string $query = null, $options = [], $page = 1) {
    return Search::all($query, $page, $options);
}
