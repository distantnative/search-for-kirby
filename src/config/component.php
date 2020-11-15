<?php

use Kirby\Cms\App;
use Kirby\Cms\Collection;

return function (App $kirby, Collection $collection, string $query = null, $params = []) {
    // only replace core component if option is true
    if ($kirby->option('search.system', true) === true) {

        // transform operator as string to options array
        if (is_string($params) === true) {
            $params = [
                'operator' => $params
            ];
        }

        return search($query, $params, $collection);
    }

    // otherwise keep native core component in place
    return $kirby->nativeComponent('search')(
        $kirby,
        $collection,
        $query,
        $params
    );
};
