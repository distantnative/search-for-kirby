<?php

namespace Kirby\Algolia;

// Kirby dependencies
use Kirby\Cms\Pagination;
use Kirby\Cms\Collection;

/**
 * Collection wrapper for Algolia results
 *
 * @author Lukas Bestle <lukas@getkirby.com>
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Results extends Collection
{

    // Result metadata
    protected $totalCount;
    protected $processingTime;
    protected $searchQuery;
    protected $params;

    /**
     * Class constructor
     *
     * @param array $results Returned data from an Algolia search operation
     */
    public function __construct(array $results)
    {
        // Defaults in case the results are invalid
        $defaults = [
            'hits'             => [],
            'page'             => 0,
            'nbHits'           => 0,
            'nbPages'          => 0,
            'hitsPerPage'      => 20,
            'processingTimeMS' => 0,
            'query'            => '',
            'params'           => ''
        ];

        $results = array_merge($defaults, $results);

        // Convert the hits to model objects
        $hits = array_map([$this, 'toModel'], $results['hits']);

        // Remove hits that could not be converted
        $hits = array_filter($hits, function($hit) {
            return is_object($hit) === true;
        });

        // Store the results
        parent::__construct($hits);

        // Get metadata from the results
        // Algolia uses zero based page indexes while Kirby's pagination starts at 1
        $this->totalCount     = $results['nbHits'];
        $this->processingTime = $results['processingTimeMS'];
        $this->searchQuery    = $results['query'];
        $this->params         = $results['params'];

        // Paginate the collection
        $this->pagination = new Pagination([
            'page'  => $results['page'] + 1,
            'total' => $results['nbHits'],
            'limit' => $results['hitsPerPage'],
        ]);

    }

    /**
     * Returns the Algolia search parameter string
     * Useful when debugging search requests
     *
     * @return string
     */
    public function params(): string
    {
        return $this->params;
    }

    /**
    * Returns the Algolia server processing time in ms
    *
    * @return int
    */
    public function processingTime(): int
    {
        return $this->processingTime;
    }

    /**
    * Returns the search query
    *
    * @return string
    */
    public function searchQuery(): string
    {
        return $this->searchQuery;
    }

    /**
     * Converts Algolia result hit to model
     *
     * @param array $hit
     * @return \Kirby\Cms\ModelWithContent
     */
    protected function toModel(array $hit)
    {
        $kirby = kirby();

        switch ($hit['_tags']) {
            case 'pages':
                return $kirby->page($hit['objectID']);
            case 'files':
                return $kirby->file($hit['objectID']);
            case 'users':
                return $kirby->user($hit['objectID']);
        }
    }

    /**
     * Returns the total count of results for the search query
     * $results->count() returns the count of results
     * on the current pagination page
     *
     * @return int
     */
    public function totalCount(): int
    {
        return $this->totalCount;
    }
}
