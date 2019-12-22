<?php

use Kirby\Cms\App;
use Kirby\Cms\Collection;
use Kirby\Toolkit\Str;

include __DIR__ . '/vendor/autoload.php';

App::plugin('getkirby/algolia', [
    'api' => require 'src/config/api.php',
    'hooks' => require 'src/config/hooks.php',
    'translations' => [
        'en' => require 'src/config/i18n/en.php'
    ],
    'sections' => [
        'algolia' => []
    ],
    'components' => [
        'search' => function (App $kirby, Collection $collection, string $query = null, $params = []) {
            $options = [];

            // Filter index by model type
            if (is_a($collection, 'Kirby\Cms\Pages') === true) {
                $options['filters'] = 'pages';
            } else if (is_a($collection, 'Kirby\Cms\Files') === true) {
                $options['filters'] = 'files';
            } else if (is_a($collection, 'Kirby\Cms\Users') === true) {
                $options['filters'] = 'users';
            }

            // Get results from index
            $results = algolia($query, $options);

            // Make sure only results from collection are kept
            foreach ($results as $result) {
                if ($collection->has($result->id()) === false) {
                    $results->remove($result);
                }
            }

            return $results;
        }
    ]
]);

function algolia(string $query = null, $options = [], $page = 1) {
    return Kirby\Algolia\Index::instance()->search($query, $page, $options);
}
