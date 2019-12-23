<?php

namespace Kirby\Search;

use Kirby\Toolkit\Query;

/**
 * Search class
 *
 * @author Lukas Bestle <lukas@getkirby.com>
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Search
{

    use Index\hasActions;
    use Index\hasRules;
    use Index\hasSchema;

    /**
     * Singleton class instance
     *
     * @var \Kirby\Search\Search
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
     * @return \Kirby\Search\Search
     */
    public static function instance(): self
    {
        return static::$instance = static::$instance ?? new static;
    }

    /**
     * Create index data and sent to provider
     *
     * @return void
     */
    public function index(): void
    {
        $data = $this->data();
        $this->provider->replace($data);
    }

    public function data($collection = null, $type = null): array
    {
        $data = [];

        // If specific collection is defined
        if ($collection !== null) {
            $this->options['collections'] = [
                $type => $collection
            ];
        }

        foreach ($this->options['collections'] as $type => $collection) {

            // If collection is defined in query notation
            if (is_string($collection) === true) {
                $collection = (new Query($collection, [ 'site' => site(), 'kirby' => kirby()]))->result();
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

        // Start the search
        $results = $this->provider->search($query, $options, $collection);

        // Return a collection of the results
        return new Results($results);
    }
}
