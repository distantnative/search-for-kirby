<?php

namespace Kirby\Search\Index;

use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Toolkit\Query;

/**
 * Schema alterations
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @author Lukas Bestle <lukas@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
trait hasSchema
{
    /**
     * Converts a model into a data array for Algolia
     * Uses the configuration options algolia.fields and algolia.templates
     *
     * @param  ModelWithContent $model
     * @return array
     */
    public function toEntry(ModelWithContent $model, $type): array
    {
        $fields    = $this->options['fields'][$type];
        $templates = $this->options['templates'][$type] ?? [];

        // Type is excluded from index
        if ($fields === false) {
            return [];
        }

        // Get model template based on type
        switch ($type) {
            case 'pages':
                $template = $model->intendedTemplate()->name();
                break;
            case 'files':
                $template = $model->template();
                break;
            case 'users':
                $template = $model->role()->name();
                break;
        }


        // Merge fields with the default fields and
        // standardize array structure
        $fields = array_merge(
            static::toFields($fields),
            static::toFields(is_array($templates) === true ? ($templates[$template]['fields'] ?? []) : [])
        );

        // Build resulting data array
        $data = [];

        foreach ($fields as $name => $callback) {

            // Callback method for field
            if (is_callable($callback) === true) {
                $data[$name] = call_user_func($callback, $model);

            // Field method without parameters
            } else if (is_string($callback) === true) {
                $result = $model->$name();

                if (is_a($result, Field::class) === false) {
                    $result = new Field($model, $name, $result);
                }

                $result = $result->$callback();

                // Make sure that the result is not an object
                $data[$name] = is_object($result) ? (string)$result : $result;

            // Field method with parameters
            } else if (is_array($callback) === true) {
                $result = $model->$name();

                // Skip invalid definitions
                if(isset($callback[0]) === false) {
                    $data[$name] = (string)$result;
                    continue;
                }

                if(!($result instanceof Field)) {
                    $result = new Field($model, $name, $result);
                }

                $parameters = array_slice($callback, 1);
                $callback   = $callback[0];
                $result     = call_user_func_array(array($result, $callback), $parameters);

                // Make sure that the result is not an object
                $data[$name] = (is_object($result))? (string)$result : $result;

            // No or invalid operation, convert to string
            } else {
                $data[$name] = (string)$model->$name();
            }
        }

        $data['id']    = $model->id();
        $data['_type'] = $type;

        return $data;
    }

    /**
     * Makes an array of fields and callbacks consistent
     * for Index::format
     *
     * @param  array $fields
     * @return array
     */
    static protected function toFields(array $fields): array
    {
        $result = [];

        foreach($fields as $name => $callback) {
            // Make sure the name is always the key,
            // even if no callback method was given
            if (is_int($name) === true) {
                $name = $callback;
                $callback = null;
            }

            $result[$name] = $callback;
        }

        return $result;
    }

    /**
     * Turns a query string to collection
     *
     * @param string $query
     *
     * @return \Kirby\Cms\Collection
     */
    protected function toCollection(string $query)
    {
        $query = new Query($query, [
            'site' => site(),
            'kirby' => kirby()
        ]);
        return $query->result();
    }

    /**
     * Return type for collection
     *
     * @param \Kirby\Cms\Collection|null $collection
     *
     * @return string|false
     */
    static public function toCollectionType($collection = null)
    {
        if (is_a($collection, 'Kirby\Cms\Pages') === true) {
            return 'pages';
        } else if (is_a($collection, 'Kirby\Cms\Files') === true) {
            return 'files';
        } else if (is_a($collection, 'Kirby\Cms\Users') === true) {
            return 'users';
        }

        return false;
    }
}
