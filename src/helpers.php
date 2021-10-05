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

function sanitize_field($field) {
  // prevent text collapse when removing <br>
  $field = preg_replace('/<br>/', ' ', $field);
  return preg_replace('/\s+|-/m', ' ', Str::unhtml($field));
}
