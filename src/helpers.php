<?php

use Kirby\Search\Index;

function search(string $query = null, $options = [], $collection = null)
{
    return Index::instance()->search(
        $query,
        $options,
        $collection
    );
}
