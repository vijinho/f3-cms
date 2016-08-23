<?php

namespace FFCMS\Models;

use FFMVC\Helpers;
use FFCMS\{Traits, Mappers};

/**
 * ConfigData Model Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class ConfigData extends DB
{

    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param \Log $logger
     * @param \DB\SQL $db
     */
    public function __construct(array $params = [], \Log $logger = null, \DB\SQL $db = null)
    {
        parent::__construct($params, $logger, $db);

        $this->mapper = new Mappers\ConfigData;
    }

    /**
     * get config_data table values for given keys
     *
     * @param array $keys config_data keys to load
     */
    public function getValues(array $keys = []): array
    {
        $f3 = \Base::instance();
        $db = \Registry::get('db');
        $m = $this->getMapper();
        $data = [];

        // tidy up keys for query
        $keys = array_unique($keys);
        ksort($keys);
        $keys = array_map(function($key) use ($db) {
            return $db->quote($key);
        }, $keys);

        // load keys query
        $sql = sprintf("%s IN (%s)", $db->quotekey('key'), join(',', $keys));

        // execute query, count results
        $results = $m->load($sql);
        $count = $results->count();
        if ($count == 0) {
            return $data;
        }

        // get values and return them
        do {
            $data[$m->key]= $m->value;
        }
        while ($m->skip());

        return $data;
    }
}
