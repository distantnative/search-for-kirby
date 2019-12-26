<?php

namespace Kirby\Search\Providers;

use Kirby\Search\Index;
use Kirby\Search\Provider;
use Kirby\Search\Results;

use Fuse\Fuse as Client;

/**
 * Fuse provider
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Fuse extends Provider
{

    /**
     * Default options for Fuse provider
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [
            'minMatchCharLength' => 2,
            'threshold'          => 0.4,
            'distance'           => 60,
            'findAllMatches'     => true
        ];
    }

    /**
     * Run search query against database index
     *
     * @param string $query
     * @param array $options
     * @param \Kirby\Cms\Collection|null $collection
     *
     * @return \Kirby\Search\Results;
     */
    public function search(string $query, array $options, $collection = null)
    {
        // Generate options with defaults
        $options = array_merge($this->options, $options);

        // Get searchable data
        $data = $this->data($collection);

        // Get all fields and remove unsearchable fields
        $keys = $this->fields($data);

        // Create search instance and run with query
        $results = (new Client($data, array_merge(
            $options,
            ['keys' => $keys]
        )))->search($query);

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

    /**
     * Get data for search index
     *
     * @param \Kirby\Cms\Collection|null $collection
     *
     * @return array
     */
    protected function data($collection = null): array
    {
        $index = Index::instance();

        // If specific collection is defined,
        // turn these models into processable data
        if ($collection !== null) {
            $type = Index::toCollectionType($collection);

            return $collection->toArray(function ($model) use ($index, $type) {
                return $index->toEntry($model, $type);
            });
        }

        // Otherwise get full index data
         return $index->data();
    }

    /**
     * The Fuse provider does not feature any persistent
     * index. The following methods are not applicable.
     */
    public function replace(array $objects): void {}
    public function insert(array $object): void {}
    public function delete(string $id): void {}
}
