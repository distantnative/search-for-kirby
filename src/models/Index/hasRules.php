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
     * Checks if a specific model should be included in the Algolia index
     * Uses the configuration option algolia.templates
     *
     * @param  ModelWithContent $model
     * @return bool
     */
    protected function isIndexable(ModelWithContent $model, string $type)
    {
        // Get model type specific options
        $templates = $this->options['templates'][$type] ?? [];

        // Check for the filter function
        if ($templates instanceof Closure) {
            return call_user_func($templates, $model) !== false;
        }

        // Model type generally excluded
        if ($templates === false) {
            return false;
        }

        // If none are defined, all are allowed
        if (empty($templates) === true) {
            return true;
        }

        switch ($type) {
            case 'pages':
                $template = $model->intendedTemplate()->name();
                break;
            case 'files':
                $template = $model->template();
                break;
            case 'users':
                $template = $model->role();
                break;
        }

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

        // Skip every value that is not a boolean or array for consistency
        if (is_array($template) === false) {
            return false;
        }

        // No rule was violated, the page is indexable
        return true;
    }
}
