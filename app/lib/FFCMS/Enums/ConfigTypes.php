<?php

namespace FFCMS\Enums;

use vijinho\Enums\Enum;

/**
 * ConfigTypes Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class ConfigTypes extends Enum
{
    /**
     * always capitalize enum keys?
     *
     * @var bool
     */
    protected static $capitalize = false;

    /**
     * case-sensitive check when searching by key for a value?
     *
     * @var bool
     */
    protected static $case_sensitive = false;

    /**
     * enum values
     *
     * @var array $values
     */
    protected static $values = [
        'text'         => 'Text',
        'textarea'     => 'Textarea',
        'url'          => 'URL',
        'email'        => 'Email',
        'html'         => 'HTML',
        'markdown'     => 'Markdown',
        'json'         => 'JSON',
        'yaml'         => 'YAML',
        'numeric'      => 'Numeric',
        'whole_number' => 'Whole Number',
        'integer'      => 'Integer',
        'float'        => 'Float',
        'boolean'      => 'Boolean',
        'float'        => 'Float',
        'date'         => 'Date',
    ];
}
