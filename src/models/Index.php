<?php

namespace Kirby\Search;

/**
 * Index class
 *
 * @author Lukas Bestle <lukas@getkirby.com>
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Index
{

    use Index\hasActions;
    use Index\hasRules;
    use Index\hasSchema;

    /**
     * Singleton class instance
     *
     * @var \Kirby\Search\Index
     */
    public static $instance;

    /**
     * Search provider
     *
     * @var \Kirby\Search\Provider
     */
    protected $provider;

    /**
     * Config settings
     *
     * @var array
     */
    public $options;

    public function __construct()
    {
        $this->options = array_merge(
            require __DIR__ . '/../config/options.php',
            option('search', [])
        );

        $provider = $this->options['provider'] ?? 'fuse';
        $provider = 'Kirby\\Search\\Providers\\' . ucfirst($provider);
        $this->provider = new $provider($this);
    }

    /**
     * Returns a singleton instance of the Algolia class
     *
     * @return \Kirby\Search\Index
     */
    public static function instance(): self
    {
        return static::$instance = static::$instance ?? new static;
    }

    /**
     * Send data to provider for creating index
     *
     * @return void
     */
    public function build(): void
    {
        $data = $this->data();
        $this->provider->replace($data);
    }

    /**
     * Create data from all models
     *
     * @return array
     */
    public function data(): array
    {
        $data = [];

        foreach ($this->options['collections'] as $type => $collection) {

            // If collection is defined in query notation
            if (is_string($collection) === true) {
                $collection = $this->toCollection($collection);
            }

            foreach ($collection as $model) {
                if ($this->isIndexable($model, $type) === true) {
                    $data[] = $this->toEntry($model, $type);
                }
            }
        }

        return $data;
    }

    /**
     * Search in index
     *
     * @param string $query
     * @param array $options
     * @param \Kirby\Cms\Collection $collection
     *
     * @return \Kirby\Search\Results
     */
    public function search(string $query = null, array $options = [], $collection = null)
    {
        // Don't search if nothing is queried
        if ($query === null || $query === '') {
            return new Results([]);
        }

        // Add default pagination
        $options['page']  = $options['page'] ?? 1;
        $options['limit'] = $options['limit'] ?? $this->options['limit'];

        // Return a collection of the results
        return $this->provider->search($query, $options, $collection);
    }
}
