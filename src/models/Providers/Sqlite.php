<?php

namespace Kirby\Search\Providers;

use Kirby\Search\Index;
use Kirby\Search\Provider;
use Kirby\Database\Db;
use Kirby\Toolkit\Dir;

/**
 * Sqlite provider
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
class Sqlite extends Provider
{

    /**
     * Constructor
     *
     * @param \Kirby\Search\Search $search
     */
    public function __construct(Index $index)
    {
        $this->options = $index->options['sqlite'] ?? [];

        if (file_exists(dirname($this->options['root'])) === false) {
            Dir::make(dirname($this->options['root']));
        }

        // Connect to sqlite database
        Db::connect([
            'type'     => 'sqlite',
            'database' => $this->options['root']
        ]);
    }

    /**
     * Create FTS5 based virtual table and insert
     * all objects
     *
     * @param array $data
     * @return void
     */
    public function replace(array $data): void
    {
        // Get all field names for columns to be created
        $columns = $this->fields($data);
        $columns[] = 'objectID UNINDEXED';
        $columns[] = '_tags UNINDEXED';

        // Drop and create fresh virtual table
        Db::query('DROP TABLE IF EXISTS models');
        Db::query('CREATE VIRTUAL TABLE models USING FTS5(' . implode(',', $columns) . ', tokenize = "porter unicode61");');

        // Insert each object into the table
        foreach ($data as $entry) {
            $this->insert($entry);
        }
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

        // Get results from database
        $results = Db::query('SELECT * FROM models("' . $query. '*") ORDER BY rank;');

        // Turn into array
        if ($results !== false) {
            $results = $results->toArray(function ($result) {
                return $result->toArray();
            });
        } else {
            $results = [];
        }

        // Make sure only results from collection are kept
        $results = $this->filterByCollection($results, $collection);

        return $this->toResults($results, $options);
    }

    public function insert(array $object): void
    {
        Db::insert('models', $object);
    }

    public function update(array $object): void
    {
        Db::update(
            'models',
            $object,
            ['objectID' => $object['objectID']]
        );
    }

    public function delete(string $id): void
    {
        Db::delete('models', ['objectID' => $id]);
    }
}
