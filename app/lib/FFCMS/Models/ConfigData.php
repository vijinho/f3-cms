<?php

namespace FFCMS\Models;

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
     * @var \FFCMS\Mappers\ConfigData
     */
    public $mapper;

    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param null|\Log $logger
     * @param null|\DB\SQL $db
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
        $m = $this->getMapper();
        $data = [];

        // tidy up keys for query
        $keys = array_unique($keys);
        ksort($keys);
        $keys = array_map(function($key) use ($m) {
            return $m->quote($key);
        }, $keys);

        // load keys query
        $sql = sprintf("%s IN (%s)", $m->quotekey('key'), join(',', $keys));

        // execute query, count results
        $results = $m->load($sql);
        $count = count($results);
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
