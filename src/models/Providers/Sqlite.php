<?php

namespace Kirby\Search\Providers;

use Kirby\Search\Index;
use Kirby\Search\Provider;
use Kirby\Search\Results;

use Kirby\Database\Database;
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
     * Additional characters to be considered
     * as part of tokens
     *
     * @var string
     */
    protected static $tokenize = '@';

    /**
     * Constructor
     *
     * @param \Kirby\Search\Index $search
     */
    public function __construct(Index $index)
    {
        parent::__construct($index);

        // Create root directory
        $dir = dirname($this->options['root']);

        if (file_exists($dir) === false) {
            Dir::make($dir);
        }

        // Connect to sqlite database
        $this->store = new Database([
            'type'     => 'sqlite',
            'database' => $this->options['root']
        ]);
    }

    /**
     * Default options for Sqlite provider
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [
            'root'  => dirname(__DIR__, 6) . '/media/search',
            'fuzzy' => true
        ];
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
        $this->store->execute('DROP TABLE IF EXISTS models');
        $this->store->execute(
            'CREATE VIRTUAL TABLE models USING FTS5(' . $this->store->escape(implode(',', $columns)) . ', tokenize="unicode61 tokenchars \'' . $this->store->escape(static::$tokenize) . '\'");'
        );

        // Insert each object into the table
        foreach ($data as $entry) {
            $this->insert($entry);
        }
    }

    /**
     * Creates value representing each state of the fields'
     * string where you take away the first letter.
     * Needed for lookups in the middle or end of text.
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

            // Add original string to the beginning
            $data[$field] = $value;

            // Split into words/tokens
            $words  = str_word_count($value, 1, static::$tokenize);

            // Foreach token
            foreach ($words as $word) {
                while (strlen($word) > 0) {
                    // Remove first character and add to value,
                    // then repeat until the end of the word
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

        // Define pagination data
        $page   = $options['page'];
        $offset = ($options['page'] - 1) * $options['limit'];
        $limit  = $options['limit'];

        // Define SQL for search query
        $tokens = str_word_count($query, 1, static::$tokenize);
        if (count($tokens) > 1) {
            $tokens = $this->store->escape(implode('* ', $tokens) . '*');
            $query = 'NEAR(' . $tokens . ')';
        } else {
            $tokens = $this->store->escape($query);
            $query = '"' . $tokens . '"*';
        }

        // Get matches from database
        // with limit and offset
        $data = $this->store->models()
            ->select('id, _type')
            ->where('models MATCH \'' . $query . '\'')
            ->order('rank')
            ->offset($offset)
            ->limit($limit)
            ->fetch('array')->all();

        // If no matches found
        if ($data === false) {
            return new Results([]);
        }

        // Make sure only results from collection are kept
        $results = $this->filterByCollection($data->toArray(), $collection);

        return new Results([
            'hits'  => $results,
            'page'  => $page,
            'total' => $data->count(),
            'limit' => $limit
        ]);
    }

    public function insert(array $object): void
    {
        if ($this->options['fuzzy'] === true) {
            $object = $this->fuzzify($object);
        }

        $this->store->models()->insert($object);
    }

    public function delete(string $id): void
    {
        $this->store->models()->delete(['id' => $id]);
    }
}
