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
        // Merge options with defaults
        $provider = lcfirst(get_class());

        $this->options = array_merge(
            $this->defaults(),
            $index->options[$provider] ?? []
        );
    }

    /**
     * Default options for provider
     *
     * @return array
     */
    abstract protected function defaults(): array;

    /**
     * Search index for query hits
     *
     * @param string $query
     * @param array $options
     * @param \Kirby\Cms\Collection|null $collection
     *
     * @return \Kirby\Search\Results;
     */
    abstract public function search(string $query, array $options, $collection = null);

    /**
     * Turn results array to Results object with pagination
     *
     * @param array $results
     * @param array $options
     *
     * @return \Kirby\Search\Results;
     */
    protected function toResults(array $results, array $options)
    {
        $page   = $options['page'];
        $offset = ($options['page'] - 1) * $options['limit'];
        $limit  = $options['limit'];

        return new Results([
            'hits'  => array_slice($results, $offset, $limit),
            'page'  => $page,
            'total' => count($results),
            'limit' => $limit
        ]);
    }

    abstract public function replace(array $objects): void;
    abstract public function insert(array $object): void;
    abstract public function delete(string $id): void;
    public function update(string $id, array $object): void
    {
        $this->delete($id);
        $this->insert($object);
    }

    /**
     * Returns array of field names for models array
     *
     * @param array $data
     * @return array
     */
    protected function fields(array $data): array
    {
        $fields = call_user_func_array('array_merge', $data);

        // Remove unsearchable fields
        unset($fields['id'], $fields['_type']);

        return array_keys($fields);
    }

    /**
     * Filter results to only include those that are
     * part of the collection
     *
     * @param array $results
     * @param \Kirby\Cms\Collection $collection
     *
     * @return array
     */
    protected function filterByCollection(array $results, $collection = null): array
    {
        // If no collection exists, return all results
        if ($collection === null) {
            return $results;
        }

        // Otherwise remove the results that are not
        // part of the collection
        return array_filter($results, function ($result) use ($collection) {
            return $collection->has($result['id']);
        });
    }
}