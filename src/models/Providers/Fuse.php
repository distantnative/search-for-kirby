<?php

namespace Kirby\Search\Providers;

use Kirby\Search\Search;
use Kirby\Search\Provider;

// Vendor dependencies
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
     * Constructor
     *
     * @param \Kirby\Search\Search $search
     */
    public function __construct(Search $search)    {
        $this->options = $search->options['fuse'] ?? [];
        $this->index   = $search;
    }

    public function replace(array $objects): void {}

    public function search(string $query, array $options, $collection = null): array
    {
        // Generate options with defaults
        $options = array_merge($this->options, $options);
        $data    = $this->index->data($collection, $options['filters'] ?? null);

        // Get all fields and remove unsearchable fields
        $keys = call_user_func_array('array_merge', $data);
        unset($keys['objectID'], $keys['_tags']);
        $keys = array_keys($keys);

        // Create search instance
        $fuse = new Client($data, array_merge(
            $options,
            ['keys' => $keys]
        ));

        // Run search
        $results = $fuse->search($query);

        // Return with pagination info
        $offset = ($options['page'] - 1) * $options['limit'];

        return [
            'hits'  => array_slice($results, $offset, $options['limit']),
            'page'  => $options['page'],
            'total' => count($results),
            'limit' => $options['limit']
        ];
    }

    public function insert(array $object): void {}
    public function update(array $object): void {}
    public function delete(string $id): void {}
}
