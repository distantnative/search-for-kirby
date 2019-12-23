<?php

namespace Kirby\Search\Providers;

use Exception;
use Kirby\Search\Search;
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
     * Current index
     */
    protected $index;

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
    public function __construct(Search $search)
    {
        $this->options = $search->options['algolia'] ?? [];

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

        $this->index = $this->algolia->initIndex($this->options['index'] ?? 'kirby');
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
        $options = array_merge($this->options, $options);

        // Set the page parameter: Algolia uses zero based page indexes
        // while Kirby's pagination starts at 1
        $options['page'] = $options['page'] - 1;

        // Map the plugin option to algolia option
        $options['hitsPerPage'] = $options['limit'];

        // Start the search
        $results = $this->index->search($query, $options);

        // Algolia uses zero based page indexes
        //while Kirby's pagination starts at 1
        return [
            'hits'  => $results['hits'],
            'page'  => $results['page'] + 1,
            'total' => $results['nbHits'],
            'limit' => $results['hitsPerPage']
        ];
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
