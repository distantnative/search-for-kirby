<?php

namespace Kirby\Search;


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
    protected $options;

    public function __construct()
    {
        $this->options = array_merge(
            require __DIR__ . '/../config/options.php',
            option('search', [])
        );

        $provider = $this->options['provider'] ?? 'sqlite';
        $provider = 'Kirby\\Search\\Provider\\' . ucfirst($provider);
        $this->provider = new $provider($this->options);
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
     * Create index in Algolia
     *
     * @return void
     */
    public function build(): void
    {
        $objects = [];

        foreach ($this->options['collections'] as $type => $collection) {
            foreach ($collection as $model) {
                if ($this->isIndexable($model, $type) === true) {
                    $objects[] = $this->toEntry($model, $type);
                }
            }
        }

        $this->provider->replace($objects);
    }

    /**
     * Search in index
     *
     * @param string $query
     *
     * @return array
     */
    public function search(string $query, array $options)
    {
        return $this->provider->search($query, $options);
    }
}
