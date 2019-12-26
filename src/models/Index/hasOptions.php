<?php

namespace Kirby\Search\Index;

/**
 * Index options
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @author Lukas Bestle <lukas@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
trait hasOptions
{
    protected function collections(): array
    {
        $defaults = [
            'pages' => $pages = 'site.index.filterBy("isReadable", true)',
            'files' => $pages . '.files',
            'users' => 'kirby.users'
        ];

        return array_merge(
            $defaults,
            $this->options['collections'] ?? []
        );
    }

    protected function fields(string $type = null)
    {
        $defaults = [
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
        ];

        $fields = array_merge(
            $defaults,
            $this->options['fields'] ?? []
        );

        if ($type === null) {
            return $fields;
        }

        return $fields[$type] ?? [];
    }

    protected function templates(string $type = null)
    {
        $defaults = [
            'pages' => function ($model) {
                return $model->id() !== 'home' && $model->id() !== 'error';
            }
        ];

        $templates = array_merge(
            $defaults,
            $this->options['templates'] ?? []
        );

        if ($type === null) {
            return $templates;
        }

        return $templates[$type] ?? [];
    }
}
