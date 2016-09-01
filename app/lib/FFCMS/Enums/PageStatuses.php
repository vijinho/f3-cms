<?php

namespace FFCMS\Enums;

use vijinho\Enums\Enum;

/**
 * Page Statuses Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class PageStatuses extends Enum
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
        'draft',
        'review',
        'published',
        'unpublished',
        'expired',
    ];
}
