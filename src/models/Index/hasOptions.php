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

    /**
     * Returns merged array of index entries based on defaults
     * and config option
     *
     * @return array
     */
    protected function entries(): array
    {
        $defaults = [
            'pages' => $pages = 'site.index',
            'files' => $pages . '.files',
            'users' => 'kirby.users'
        ];

        return array_merge(
            $defaults,
            $this->options['entries'] ?? []
        );
    }

    /**
     * Returns merged array of fields based on defaults
     * and config option
     *
     * @return array
     */
    protected function fields(string $type = null)
    {
        $defaults = [
            'pages' => [
                'title',
                'text'
            ],
            'files' => [
                'filename',
                'caption'
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

    /**
     * Returns merged array of templates based on defaults
     * and config option
     *
     * @return array
     */
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
