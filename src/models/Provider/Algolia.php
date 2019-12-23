<?php

namespace Kirby\Search\Provider;

use Exception;
use Kirby\Search\Provider;

// Vendor dependencies
use Algolia\AlgoliaSearch\SearchClient as Client;

/**
 * Algolia provider
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Algolia extends Provider
{

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

    public function __construct(array $options = [])
    {
        parent::__construct($options['algolia'] ?? []);

        if (isset($this->options['app'], $this->options['key']) === false) {
            throw new Exception('Please set your Algolia API credentials in the Kirby configuration.');
        }

        $this->algolia = Client::create(
            $this->options['app'],
            $this->options['key']
        );

        $this->index = $this->algolia->initIndex($options['index']);
    }

    public function replace(array $objects): void
    {
        $this->index->setSettings([
            'customRanking' => ['desc(_tags)']
        ]);

        $this->index->replaceAllObjects($objects);
    }

    public function search(string $query, array $options): array
    {
        // Generate options with defaults
        $defaults = $this->options['options'] ?? [];
        $options  = array_merge($defaults, $options);

        // Set the page parameter: Algolia uses zero based page indexes
        // while Kirby's pagination starts at 1
        $options['page'] = $options['page'] ? $options['page'] - 1 : 0;

        // Start the search
        return $this->index->search($query, $options);
    }

    public function insert(array $object): void
    {
        $this->index->saveObject($object);
    }

    public function update(array $object): void
    {
        $this->index->saveObject($object);
    }

    public function delete(string $id): void
    {
        $this->index->deleteObject($id);
    }
}
