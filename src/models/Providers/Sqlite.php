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
        $this->setOptions($index);

        // Create root directory
        $root = $this->options['file'];

        if (is_callable($root) === true) {
            $root = call_user_func($root);
        }

        $dir = dirname($root);

        if (file_exists($dir) === false) {
            Dir::make($dir);
        }

        // Connect to sqlite database
        $this->store = new Database([
            'type'     => 'sqlite',
            'database' => $root
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
            'file'    => dirname(__DIR__, 5) . '/logs/search.sqlite',
            'fuzzy'   => true,
            'weights' => [
                'title'    => 5,
                'filename' => 5,
                'email'    => 5,
                'name'     => 5
            ]
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

        // Construct query based on tokens:
        // split query along whitespace
        preg_match_all(
            '/[\pL\pN\pPd]+/u',
            $query,
            $tokens
        );
        $tokens = $tokens[0];

        // check if query already contains qualified operators
        $qualified = in_array('AND', $tokens) ||
            in_array('OR', $tokens) ||
            in_array('NOT', $tokens);

        // append * to all tokens except operators
        $tokens = array_map(function ($token) {
            return in_array($token, ['AND', 'OR', 'NOT']) ? $token : $token . '*';
        }, $tokens);

        // merge query again, if unqualified insert OR operator
        $query = implode($qualified ? ' ' : (' OR '), $tokens);

        // get matches from database
        try {
            $data = $this->store->models()
                ->select('id, _type')
                ->where('models MATCH \'' . $this->store->escape($query) . '\'');
        } catch (\Exception $error) {
            return new Results([]);
        }


        // Custom weights for ranking
        if (is_array($this->options['weights']) === true) {

            // Get all columns from table
            $columns = $this->store->query('PRAGMA table_info(models);')->toArray();

            // Match columns to custom weights
            $weights = array_map(function ($column) {
                return $this->options['weights'][$column->name()] ?? 1;
            }, $columns);

            // Add Sqlite clause to weigh ranking
            $weights = implode(', ', $weights);
            $data    = $data->andWhere('rank MATCH \'bm25(' . $weights . ')\'');
        }

        // Fetch all data as array
        // with limit and offset
        $data = $data
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
        if ($this->options['fuzzy'] !== false) {
            $object = $this->fuzzify($object);
        }

        $this->store->models()->insert($object);
    }

    public function delete(string $id): void
    {
        $this->store->models()->delete(['id' => $id]);
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
        foreach ($data as $field => $value) {
            // Don't fuzzify unsearchable fields
            if ($field === 'id' || $field === '_type') {
                continue;
            }

            // Make sure to only fuzzify fields according to config
            if (
                $this->options['fuzzy'] !== true &&
                in_array($field, $this->options['fuzzy'][$data['_type']] ?? []) === false
            ) {
                continue;
            }

            // Add original string to the beginning
            $data[$field] = $value;

            // Split into words/tokens
            $words = str_word_count($value, 1, static::$tokenize);

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
}
