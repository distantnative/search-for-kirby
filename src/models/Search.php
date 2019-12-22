<?php

namespace Kirby\Algolia;

use Kirby\Cms\Collection;
use Kirby\Toolkit\Str;

/**
 * Search class for Algolia
 *
 * @author Lukas Bestle <lukas@getkirby.com>
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Search
{

    /**
     * @return \Kirby\Algolia\Results
     */
    static public function all(string $query = null, $page = 1, array $options = [])
    {
        // Don't search if nothing is queried
        if ($query === null || $query === '') {
            return new Results([]);
        }

        // Set the page parameter: Algolia uses zero based page indexes
        // while Kirby's pagination starts at 1
        $options['page'] = $page ? $page - 1 : 0;

        // Start the search
        $results = Index::instance()->search($query, $options);

        // Return a collection of the results
        return new Results($results);
    }

    static public function collection(Collection $collection, string $query = null, $params = [])
    {
        $options = [];

        // Filter index by model type
        if (is_a($collection, 'Kirby\Cms\Pages') === true) {
            $options['filters'] = 'pages';
        } else if (is_a($collection, 'Kirby\Cms\Files') === true) {
            $options['filters'] = 'files';
        } else if (is_a($collection, 'Kirby\Cms\Users') === true) {
            $options['filters'] = 'users';
        }

        // Get results from index
        $results = static::all($query, null, $options);

        // Make sure only results from collection are kept
        foreach ($results as $result) {
            if ($collection->has($result->id()) === false) {
                $results->remove($result);
            }
        }

        return $results;
    }
}
