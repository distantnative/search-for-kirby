<?php

namespace Kirby\Search\Index;

use Kirby\Cms\ModelWithContent;

/**
 * Modification actions
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
trait hasActions
{

    /**
     * Inserts an object into the index
     * Used by Panel hooks
     *
     * @param \Kirby\Cms\ModelWithContent $model
     */
    public function insert(ModelWithContent $model)
    {
        if ($this->isIndexable($model) === false) {
            return false;
        }

        $object = $this->toEntry($model);
        $this->provider->insert($object);
    }

    /**
     * Updates a page in the index
     * Used by Panel hooks
     *
     * @param \Kirby\Cms\ModelWithContent $model
     */
    public function update(string $id, ModelWithContent $model)
    {
        if ($this->isIndexable($model) === false) {
            $this->delete($model);
            return false;
        }

        $object = $this->toEntry($model);
        $this->provider->update($id, $object);
    }

    /**
     * Moves a page in the index
     * Used by Panel hooks
     *
     * @param \Kirby\Cms\ModelWithContent $old
     * @param \Kirby\Cms\ModelWithContent $new
     */
    public function move(ModelWithContent $old, ModelWithContent $new)
    {
        // Delete the old object
        $this->delete($old);

        // Insert the new object
        $this->insert($new);
    }

    /**
     * Deletes a page from the index
     *
     * @param ModelWithConent|string $id model or ID
     * @param \Kirby\Cms\ModelWithContent|string $id
     */
    public function delete($id)
    {
        if (is_a($id, 'Kirby\Cms\ModelWithContent') === true) {
            $id = $id->id();
        }

        $this->provider->delete($id);
    }
}
