<?php

namespace Kirby\Search\Providers;

use Exception;

use Kirby\Search\Index;
use Kirby\Search\Provider;
use Kirby\Search\Results;

use Algolia\AlgoliaSearch\SearchClient as Client;

/**
 * Algolia provider
 *
 * @author Lukas Bestle <lukas@getkirby.com>
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
     * @param \Kirby\Search\Index $search
     */
    public function __construct(Index $index)
    {
        parent::__construct($index);

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
     * Default options for Algolia provider
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [];
    }

    /**
     * Fill Algolia index with data
     *
     * @param array $data
     * @return void
     */
    public function replace(array $data): void
    {
        $this->store->setSettings([
            'customRanking' => ['desc(_type)']
        ]);

        $this->store->clearObjects();
        $this->store->saveObjects($data, [
            'objectIDKey' => 'id'
        ]);
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

        // Filter by collection type
        if ($type = Index::toCollectionType($collection)) {
            $options['options']['filters'] = '_type:' . $type;
        }

        // Start the search
        $results = $this->store->search($query, array_merge([
            // Set the page parameter: Algolia uses zero based page
            // indexes while Kirby's pagination starts at 1
            'page' => $options['page'] - 1,

            // Map the plugin option to algolia option
            'hitsPerPage' => $options['limit']
        ], $options['options']));

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
        $this->store->saveObject($object, [
            'objectIDKey' => 'id'
        ]);
    }

    public function delete(string $id): void
    {
        $this->store->deleteObject($id);
    }
}
