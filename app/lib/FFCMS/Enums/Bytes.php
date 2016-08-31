<?php

namespace FFCMS\Enums;

use vijinho\Enums\Enum;

/**
 * User Scopes Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Bytes extends Enum
{
    /**
     * always capitalize enum keys?
     *
     * @var bool
     */
    protected static $capitalize = true;

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
        'bit'      => 1,
        'byte'     => 8,
        'kilobyte' => 8 * 1024,
        'megabyte' => 8 * 1024 * 1024,
        'gigabyte' => 8 * 1024 * 1024 * 1024,
        'terabyte' => 8 * 1024 * 1024 * 1024 * 1024,
    ];
}
