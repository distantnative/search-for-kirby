<?php

use Kirby\Cms\App;
use Kirby\Cms\Collection;
use Kirby\Search\Search;

include __DIR__ . '/vendor/autoload.php';

App::plugin('getkirby/search', [
    'api'   => require 'src/config/api.php',
    'hooks' => require 'src/config/hooks.php',
    'translations' => [
        'en' => require 'src/config/i18n/en.php'
    ],
    'sections' => [
        'search' => []
    ],
    'components' => [
        'search' => function (App $kirby, Collection $collection, string $query = null, $params = []) {
            return Search::collection($collection, $query, $params);
        }
    ]
]);

function search(string $query = null, $options = []) {
    return Search::all($query, $options);
}
