<?php

namespace Kirby\Algolia;


// PHP dependencies
use Exception;

// Vendor dependencies
use Algolia\AlgoliaSearch\SearchClient as Client;

// Kirby dependencies
use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;

/**
 * Index class for Algolia
 *
 * @author Lukas Bestle <lukas@getkirby.com>
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Index
{

    use hasModifiers;
    use hasRestrictions;

    /**
     * Singleton class instance
     *
     * @var \Kirby\Algolia\Index
     */
    public static $instance;

    /**
     * Algolia client instance
     *
     * @var \Algolia\AlgoliaSearch\SearchClient
     */
    protected $algolia;

    /**
     * Algolia index
     */
    protected $index;

    /**
     * Config settings
     *
     * @var array
     */
    protected $options;

    public function __construct()
    {
        $this->options = array_merge(
            require __DIR__ . '/../config/options.php',
            option('algolia', [])
        );

        if (isset($this->options['app'], $this->options['key']) === false) {
            throw new Exception('Please set your Algolia API credentials in the Kirby configuration.');
        }

        $this->algolia = Client::create(
            $this->options['app'],
            $this->options['key']
        );

        $this->index = $this->algolia->initIndex($this->options['index']);
    }

    /**
     * Returns a singleton instance of the Algolia class
     *
     * @return \Kirby\Algolia\Index
     */
    public static function instance(): self
    {
        return static::$instance = static::$instance ?? new static;
    }

    /**
     * Create index in Algolia
     *
     * @return void
     */
    public function build(): void
    {
        $objects = [];
        $sources = [
            'pages' => $pages = site()->index(true)->filterBy('isReadable', true),
            'files' => $pages->files(),
            'users' => kirby()->users()
        ];

        foreach ($sources as $type => $collection) {
            foreach ($collection as $model) {
                if ($this->isIndexable($model, $type) === true) {
                    $objects[] = $this->format($model, $type);
                }
            }
        }

        $this->index->setSettings([
            'customRanking' => ['desc(_tags)']
        ]);

        $this->index->replaceAllObjects($objects);

    }

    /**
     * Search in index
     *
     * @param string $query
     *
     * @return \Kirby\Algolia\Results
     */
    public function search(string $query = null, $page = 1, array $options = [])
    {
        // Don't search if nothing is queried
        if ($query === null || $query === '') {
            return new Results([]);
        }

        // Generate options with defaults
        $defaults = $this->options['options'] ?? [];
        $options  = array_merge($defaults, $options);

        // Set the page parameter: Algolia uses zero based page indexes
        // while Kirby's pagination starts at 1
        $options['page'] = $page ? $page - 1 : 0;

        // Start the search
        $results = $this->index->search($query, $options);

        // Return a collection of the results
        return new Results($results);
    }

     /**
    * Converts a model into a data array for Algolia
    * Uses the configuration options algolia.fields and algolia.templates
    *
    * @param  ModelWithContent $model
    * @return array
    */
    protected function format(ModelWithContent $model, $type): array
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
            static::standardize($fields),
            static::standardize(is_array($templates) === true ? ($templates[$template]['fields'] ?? []) : [])
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

        $data['_tags']    = $type;
        $data['objectID'] = $model->id();

        return $data;
    }

    /**
    * Makes an array of fields and callbacks consistent
    * for Index::format
    *
    * @param  array $fields
    * @return array
    */
    static protected function standardize(array $fields): array
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
}
