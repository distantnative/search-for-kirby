<?php

namespace Kirby\Search\Providers;

use Kirby\Search\Index;
use Kirby\Search\Provider;
use Kirby\Search\Results;

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
        Db::connect([
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
        Db::query('DROP TABLE IF EXISTS models;');
        Db::query('CREATE VIRTUAL TABLE models USING FTS5(' . implode(',', $columns) . ', tokenize="unicode61 tokenchars \'' . static::$tokenize . '\'");');

        // Insert each object into the table
        foreach ($data as $entry) {
            $this->insert($entry);
        }

        // IF I EVER FIND A WAY TO LOAD SPELLFIX1 extension
        // Db::query('DROP TABLE IF EXISTS terms;');
        // Db::query('CREATE VIRTUAL TABLE terms USING fts5vocab(models, "row");');
        // Db::query('DROP TABLE IF EXISTS spellings;');
        // Db::query('CREATE VIRTUAL TABLE spellings USING spellfix1;');
        // Db::query('INSERT INTO spellings(word) SELECT term FROM terms WHERE col='*';');
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

        // Define pagination data
        $page   = $options['page'];
        $offset = ($options['page'] - 1) * $options['limit'];
        $limit  = $options['limit'];

        // Define SQL for search query
        $tokens =  str_word_count($query, 1, static::$tokenize);
        if (count($tokens) > 1) {
            $query = 'NEAR(' . implode('* ', $tokens) . '*)';
        } else {
            $query = '"' . $query. '"*';
        }

        // Get mathes from database
        $data = Db::query('SELECT * FROM models(\'' . $query . '\') ORDER BY rank;');

        // If no matches found
        if ($data === false) {
            return new Results([]);
        }

        // Limit and offset data
        // and turn to arrays
        $results = $data->offset($offset)->limit($limit);
        $results = $results->toArray(function ($result) {
            return $result->toArray();
        });

        // Make sure only results from collection are kept
        $results = $this->filterByCollection($results, $collection);

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

        Db::insert('models', $object);
    }

    public function delete(string $id): void
    {
        Db::delete('models', ['id' => $id]);
    }
}
