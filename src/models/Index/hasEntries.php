<?php

namespace Kirby\Search\Index;

use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\Query;


/**
 * Entry collections of the index
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
trait hasEntries
{

    /**
     * Returns merged array of index entries based
     * on defaults and config option
     *
     * @param string|null $type
     * @return string|array
     */
    protected function entries(string $type = null)
    {
        $defaults = [
            'pages' => $pages = 'site.index',
            'files' => $pages . '.files',
            'users' => 'kirby.users'
        ];

        $entries = array_merge(
            $defaults,
            $this->options['entries'] ?? []
        );

        if ($type === null) {
            return $entries;
        }

        return $entries[$type] ?? [];
    }

    /**
     * Returns merged array of fields based on defaults
     * and config option
     *
     * @param string|null $type
     * @return array
     */
    protected function fields(string $type = null): array
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


    /**
     * Returns merged array of templates based on defaults
     * and config option
     *
     * @param string|null $type
     * @return array|Closure
     */
    protected function templates(string $type = null)
    {
        $defaults = [
            'pages' => function (ModelWithContent $model) {
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

    /**
     * Turns an entry query string to collection
     *
     * @param string $entry
     *
     * @return \Kirby\Cms\Collection
     */
    protected function toCollection(string $entry)
    {
        $query = new Query($entry, [
            'site' => site(),
            'kirby' => kirby()
        ]);

        return $query->result();
    }

    /**
     * Converts a model into a data array
     *
     * @param  \Kirby\Cms\ModelWithContent $model
     * @return array
     */
    public function toEntry(ModelWithContent $model): array
    {
        $type     = $this->toType($model);
        $fields   = $this->fields($type);

        if (is_array($fields) === false) {
            throw new InvalidArgumentException('Invalid fields definition');
        }

        // Build resulting data array
        $data = [];

        foreach ($fields as $name => $callback) {

            // Make sure the name is always the key,
            // even if no callback method was given
            if (is_int($name) === true) {
                $name = $callback;
                $callback = null;
            }

            // Callback method for field
            if (is_callable($callback) === true) {
                $data[$name] = call_user_func($callback, $model);
                continue;
            }

            // Field method without parameters
            if (is_string($callback) === true) {
                $result = $model->$name();

                if (is_a($result, Field::class) === false) {
                    $result = new Field($model, $name, $result);
                }

                $result = $result->$callback();

                // Make sure that the result is not an object
                $data[$name] = is_object($result) ? (string)$result : $result;
                continue;
            }

            // Field method with parameters
            if (is_array($callback) === true) {
                $result = $model->$name();

                // Skip invalid definitions
                if (isset($callback[0]) === false) {
                    $data[$name] = (string)$result;
                    continue;
                }

                if (!($result instanceof Field)) {
                    $result = new Field($model, $name, $result);
                }

                $parameters = array_slice($callback, 1);
                $callback   = $callback[0];
                $result     = call_user_func_array(array($result, $callback), $parameters);

                // Make sure that the result is not an object
                $data[$name] = (string)$result;
                continue;
            }

            // No or invalid operation, convert to string
            $data[$name] = (string)$model->$name();
        }

        $data['id']    = $model->id();
        $data['_type'] = $type;

        return $data;
    }

    /**
     * Returns type for model or collection
     *
     * @param \Kirby\Cms\ModelWithContent|\Kirby\Cms\Collection|null $input
     * @return string|bool
     */
    static public function toType($input = null)
    {
        if (
            is_a($input, 'Kirby\Cms\Page') === true ||
            is_a($input, 'Kirby\Cms\Pages') === true
        ) {
            return 'pages';
        }
        if (
            is_a($input, 'Kirby\Cms\File') === true ||
            is_a($input, 'Kirby\Cms\Files') === true
        ) {
            return 'files';
        }
        if (
            is_a($input, 'Kirby\Cms\User') === true ||
            is_a($input, 'Kirby\Cms\Users') === true
        ) {
            return 'users';
        }

        throw new InvalidArgumentException('Unknown model/collection type');
    }

    /**
     * Returns template name for provided model
     *
     * @param  \Kirby\Cms\ModelWithContent $model
     * @param  string|null $type
     * @return string
     */
    public function toTemplate(ModelWithContent $model): string
    {
        $type = $this->toType($model);

        switch ($type) {
            case 'page':
                return $model->intendedTemplate()->name();
                break;
            case 'file':
                return $model->template();
                break;
            case 'user':
                return $model->role()->name();
                break;
        }
    }
}
