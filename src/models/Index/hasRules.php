<?php

namespace Kirby\Search\Index;

use Closure;
use Kirby\Cms\ModelWithContent;

/**
 * Index rules
 *
 * @author Nico Hoffmann <nico@getkirby.com>
 * @author Lukas Bestle <lukas@getkirby.com>
 * @license MIT
 * @link https://getkirby.com
 */
trait hasRules
{

    /**
     * Runs all checks for a speciic model
     *
     * @param  \Kirby\Cms\ModelWithContent $model
     * @return bool
     */
    public function isIndexable(ModelWithContent $model): bool
    {
        return $this->hasCollection($model) &&
            $this->hasTemplate($model);
    }

    /**
     * Checks if a specific model is included in the
     * defined entry collection
     *
     * @param  \Kirby\Cms\ModelWithContent $model
     * @return bool
     */
    public function hasCollection(ModelWithContent $model): bool
    {
        $type       = $this->toType($model);
        $entry      = $this->entries($type);
        $collection = $this->toCollection($entry);
        return $collection->has($model);
    }

    /**
     * Checks if a specific model's template is allowed
     * to be indexed
     *
     * @param  \Kirby\Cms\ModelWithContent $model
     * @return bool
     */
    public function hasTemplate(ModelWithContent $model): bool
    {
        // Get model type specific options
        $type      = $this->toType($model);
        $templates = $this->templates($type);

        // Model type generally excluded
        if ($templates === false) {
            return false;
        }

        // Check for filter function
        if ($templates instanceof Closure) {
            return call_user_func($templates, $model) !== false;
        }

        // If none are defined, all are allowed
        if (empty($templates) === true) {
            return true;
        }

        // Defined via name of model's template
        $template = $this->toTemplate($model);

        // Simple whitelist: array('project')
        if (in_array($template, $templates, true) === true) {
            return true;
        }

        // Sort out pages whose template is not defined
        if (isset($templates[$template]) === false) {
            return false;
        }

        $template = $templates[$template];

        // Check if the template is defined as a boolean
        // Example: array('project' => true, 'contact' => false)
        if (is_bool($template) === true) {
            return $template;
        }

        // No matching validation, don't allow
        return false;
    }
}
