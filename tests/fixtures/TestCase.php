<?php

namespace Kirby\Search;

use Kirby\Cms\App;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function app(array $options = []): App
    {
        return new App([
            'roots' => [
                'index' => '/dev/null'
            ],
            'options' => [
                'search' => array_merge(
                    [
                        'provider' => 'mockup'
                    ],
                    $options
                )
            ],
            'site' => [
                'children' => [
                    [
                        'slug' => 'page-a',
                        'template' => 'note',
                        'content' => [
                            'title' => 'Lions are wild'
                        ]
                    ],
                    [
                        'slug' => 'page-b',
                        'template' => 'album',
                        'content' => [
                            'title' => 'From Caracas to Buenos Aires'
                        ]
                    ],
                    [
                        'slug' => 'page-c',
                        'template' => 'impressum',
                        'content' => [
                            'title' => 'Lions in Caracas'
                        ]
                    ]
                ]
            ]
        ]);
    }

    protected function index(): Index
    {
        $app = $this->app();
        return new Index($app);
    }
}
