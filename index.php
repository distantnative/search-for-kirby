<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/vendor/autoload.php';

App::plugin('distantnative/search-for-kirby', array_merge(
    [
        'api'   => require 'src/config/api.php',
        'hooks' => require 'src/config/hooks.php',
        'translations' => [
            'en' => require 'src/config/i18n/en.php',
            'de' => require 'src/config/i18n/de.php'
        ],
        'components' => [
            'search' => require 'src/config/component.php'
        ]
    ],
    require 'src/config/panel.php'
));

include_once 'src/config/helpers.php';
