<?php

use Kirby\Cms\App;
use Kirby\Cms\Collection;
use Kirby\Search\Index;

include __DIR__ . '/vendor/autoload.php';

App::plugin('getkirby/search', [
    'api'   => require 'src/config/api.php',
    'hooks' => require 'src/config/hooks.php',
    'translations' => [
        'en' => require 'src/config/i18n/en.php',
        'de' => require 'src/config/i18n/de.php'
    ],
    'sections' => [
        'search' => []
    ],
    'components' => [
        'search' => function (App $kirby, Collection $collection, string $query = null, $params = []) {
            return search($query, [], $collection);
        }
    ]
]);

function search(string $query = null, $options = [], $collection = null) {
    return Index::instance()->search(
        $query,
        $options,
        $collection
    );
}
