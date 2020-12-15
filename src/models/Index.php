<?php

namespace Kirby\Search;

use Kirby\Cms\App;
use Kirby\Exception\NotFoundException;

/**
 * Index class
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Index
{

    use Index\hasActions;
    use Index\hasEntries;
    use Index\hasRules;

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

    /**
     * @param \Kirby\Cms\App|null $kirby
     */
    public function __construct(App $kirby = null)
    {
        if ($kirby === null) {
            $kirby = kirby();
        }

        $this->options = $kirby->option('search', []);

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
        $data = $this->toData();
        $this->provider()->replace($data);
    }

    /**
     * Checks if an active index is already present
     *
     * @return bool
     */
    public function hasIndex(): bool
    {
        return $this->provider()->hasIndex();
    }

    /**
     * Returns the search provider
     *
     * @return \Kirby\Search\Provider
     */
    public function provider(): Provider
    {
        return $this->provider;
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

        // get results from provider
        $results = $this->provider()->search($query, $options, $collection);

        // Make sure only results from collection are kept
        $results['hits'] = $this->filterByCollection($results['hits'], $collection);

        // return a collection of the results
        return new Results($results);
    }

    /**
     * Create data from all models
     *
     * @return array
     */
    public function toData(): array
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
                if ($this->hasTemplate($model) === true) {
                    $data[] = $this->toEntry($model);
                }
            }
        }

        return $data;
    }
}
