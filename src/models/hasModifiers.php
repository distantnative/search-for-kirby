<?php

namespace Kirby\Algolia;

use Kirby\Cms\ModelWithContent;

/**
 * Index modifications
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @author Lukas Bestle <lukas@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
trait hasModifiers
{

    /**
     * Inserts an object into the index
     * Used by Panel hooks
     *
     * @param \Kirby\Cms\ModelWithContent $model
     * @param string $type
     */
    public function insert(ModelWithContent $model, string $type = 'pages')
    {
        if ($this->isIndexable($model, $type) === false) {
            return false;
        }

        $object = $this->format($model, $type);
        $this->index->saveObject($object);
    }

    /**
     * Updates a page in the index
     * Used by Panel hooks
     *
     * @param \Kirby\Cms\ModelWithContent $model
     * @param string $type
     */
    public function update(ModelWithContent $model, string $type = 'pages')
    {
        if ($this->isIndexable($model, $type) === false) {
            $this->delete($model);
            return false;
        }

        $object = $this->format($model, $type);
        $this->index->saveObject($object);
    }

    /**
     * Moves a page in the index
     * Used by Panel hooks
     *
     * @param \Kirby\Cms\ModelWithContent $old
     * @param \Kirby\Cms\ModelWithContent $new
     * @param string $type
     */
    public function move(ModelWithContent $old, ModelWithContent $new, string $type = 'pages')
    {
        // Delete the old object
        $this->delete($old);

        // Insert the new object
        $this->insert($new, $type);
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

        $this->index->deleteObject($id);
    }
}
