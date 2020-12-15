<?php

namespace Kirby\Search\Providers;

use Kirby\Search\Provider;
use Kirby\Search\Results;

class Mockup extends Provider
{

    protected $data = [];

    protected function defaults(): array
    {
        return [];
    }

    public function replace(array $data): void
    {
        $this->data = $data;
    }

    public function search(string $query, array $options, $collection = null): Results
    {
        return new Results([
            'hits'  => $this->data,
            'page'  => 1,
            'total' => count($this->data),
            'limit' => count($this->data)
        ]);
    }

    public function insert(array $object): void
    {
        $this->data[] = $object;
    }

    public function delete(string $id): void
    {
        $key = array_search($id, array_column($this->data, 'id'));
        unset($this->data[$key]);
    }
}
