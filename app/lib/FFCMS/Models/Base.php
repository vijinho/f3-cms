<?php

namespace FFCMS\Models;

use FFCMS\Traits as Traits;

/**
 * Base Model Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
abstract class Base extends \Prefab
{
    use Traits\Validation;

    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param array $params
     * @param null|\Log $logger
     */
    public function __construct(array $params = [], \Log $logger = null)
    {
        if (is_object($logger)) {
            \Registry::set('logger', $logger);
        }

        foreach ($params as $k => $v) {
            $this->$k = $v;
        }

        // save default validation rules and filter rules in-case we add rules
        $this->validationRulesDefault = $this->validationRules;
        $this->filterRulesDefault     = $this->filterRules;
    }
}
