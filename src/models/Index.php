<?php

namespace Kirby\Search;

use Kirby\Exception\NotFoundException;

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
    use Index\hasOptions;
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
        $this->options = option('search', []);

        $provider = $this->options['provider'] ?? 'sqlite';
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

        foreach ($this->entries() as $type => $collection) {
            // If collection is deactivated, skip
            if ($collection === false) {
                continue;
            }

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
     * Checks if an active index is already present
     *
     * @return bool
     */
    public function hasIndex(): bool
    {
        return $this->provider->hasIndex();
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
    public function search(string $query = null, array $options = [], $collection = null): Results
    {
        // don't search if nothing is queried
        if ($query === null || $query === '') {
            return new Results([]);
        }

        // stop if not index exist yet
        if ($this->hasIndex() === false) {
            throw new NotFoundException("No index");
        }

        // add default pagination
        $options['page']  = $options['page'] ?? 1;
        $options['limit'] = $options['limit'] ?? $this->options['limit'] ?? 10;

        // return a collection of the results
        $results = $this->provider()->search($query, $options, $collection);
        return new Results($results);
    }
}
