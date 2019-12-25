<?php

namespace Kirby\Search\Providers;

use Exception;
use Kirby\Search\Index;
use Kirby\Search\Provider;
use Kirby\Search\Results;

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
     * Constructor
     *
     * @param \Kirby\Search\Search $search
     */
    public function __construct(Index $index)
    {
        $this->options = $index->options['algolia'] ?? [];

        if (isset(
            $this->options['app'],
            $this->options['key']
        ) === false) {
            throw new Exception('Please set your Algolia API credentials in the Kirby configuration.');
        }

        $this->algolia = Client::create(
            $this->options['app'],
            $this->options['key']
        );

        // Initialize Algolia index
        $this->store = $this->algolia->initIndex($this->options['index'] ?? 'kirby');
    }

    /**
     * Fill Algolia index with data
     *
     * @param array $data
     * @return void
     */
    public function replace(array $objects): void
    {
        $this->store->setSettings([
            'customRanking' => ['desc(_tags)']
        ]);

        $this->store->replaceAllObjects($objects);
    }

    /**
     * Send search query to Algolia and process results
     *
     * @param string $query
     * @param array $options
     * @param \Kirby\Cms\Collection|null $collection
     *
     * @return array
     */
    public function search(string $query, array $options, $collection = null)
    {
        // Generate options with defaults
        $options = array_merge($this->options, $options);

        // Set the page parameter: Algolia uses zero based page
        // indexes while Kirby's pagination starts at 1
        $options['page'] = $options['page'] - 1;

        // Map the plugin option to algolia option
        $options['hitsPerPage'] = $options['limit'];

        // Filter by collection type
        if ($filters = Index::toCollectionType($collection)) {
            $options['filters'] = $filters;
        }

        // Start the search
        $results = $this->store->search($query, $options);

        // Make sure only results from collection are kept
        $results = $this->filterByCollection($results, $collection);

        // Algolia uses zero based page indexes
        //while Kirby's pagination starts at 1
        return new Results([
            'hits'  => $results['hits'],
            'page'  => $results['page'] + 1,
            'total' => $results['nbHits'],
            'limit' => $results['hitsPerPage']
        ]);
    }

    public function insert(array $object): void
    {
        $this->store->saveObject($object);
    }

    public function update(array $object): void
    {
        $this->store->saveObject($object);
    }

    public function delete(string $id): void
    {
        $this->store->deleteObject($id);
    }
}
