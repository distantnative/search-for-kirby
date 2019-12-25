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

    protected static $tokenize = '.,-@';

    /**
     * Constructor
     *
     * @param \Kirby\Search\Index $search
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
        $columns[] = 'id UNINDEXED';
        $columns[] = '_type UNINDEXED';

        // Drop and create fresh virtual table
        Db::query('DROP TABLE IF EXISTS models');
        Db::query('CREATE VIRTUAL TABLE models USING FTS5(' . implode(',', $columns) . ', tokenize="unicode61 tokenchars \'' . static::$tokenize . '\'");');

        // Insert each object into the table
        foreach ($data as $entry) {
            $this->insert($entry);
        }
    }

    /**
     * Creates value representing each state of the fields'
     * string where you take away the first letter.
     * Needed for contains, starts with lookup.
     *
     * @param array $data
     *
     * @return array
     */
    protected function fuzzify(array $data): array
    {
        // Don't fuzzify unsearchable fields
        foreach ($data as $field => $value) {
            if ($field === 'id' || $field === '_type') {
                continue;
            }

            $data[$field] = $value;
            $words  = str_word_count($value, 1, static::$tokenize);

            foreach ($words as $word) {
                while (strlen($word) > 0) {
                    $word = substr($word, 1);
                    $data[$field] .= ' ' . $word;
                }
            }
        }

        return $data;
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
        $results = Db::query('SELECT * FROM models(\'"' . $query. '"*\') ORDER BY rank;');

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
        if ($this->options['fuzzy'] === true) {
            $object = $this->fuzzify($object);
        }

        Db::insert('models', $object);
    }

    public function delete(string $id): void
    {
        Db::delete('models', ['id' => $id]);
    }
}
