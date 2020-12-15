<?php

namespace Kirby\Search;

/**
 * Search provider
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
abstract class Provider
{

    /**
     * Config settings
     *
     * @var array
     */
    protected $options;

    /**
     * Current index store
     */
    protected $store;

    /**
     * Constructor
     *
     * @param \Kirby\Search\Index $search
     */
    public function __construct(Index $index)
    {
        $this->setOptions($index);
    }

    /**
     * Default options for provider
     *
     * @return array
     */
    abstract protected function defaults(): array;

    /**
     * Checks if an active index is already present
     *
     * @return bool
     */
    public function hasIndex(): bool
    {
        return true;
    }

    /**
     * Set options based on config and defaults
     *
     * @param Index $index
     * @return void
     */
    protected function setOptions(Index $index): void
    {
        // Merge options with defaults
        $class = str_replace('Kirby\\Search\\Providers\\', '', get_class($this));
        $provider = lcfirst($class);

        $this->options = array_merge(
            $this->defaults(),
            $index->options[$provider] ?? []
        );
    }

    /**
     * Search index for query hits
     *
     * @param string $query
     * @param array $options
     * @param \Kirby\Cms\Collection|null $collection
     *
     * @return array;
     */
    abstract public function search(string $query, array $options, $collection = null): array;

    abstract public function replace(array $objects): void;
    abstract public function insert(array $object): void;
    abstract public function delete(string $id): void;
    public function update(string $id, array $object): void
    {
        $this->delete($id);
        $this->insert($object);
    }
}
