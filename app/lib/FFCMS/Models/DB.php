<?php

namespace FFCMS\Models;

use FFCMS\{Traits, Mappers};

/**
 * Base Database Class
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @see https://fatfreeframework.com/sql
 */
abstract class DB extends Base
{
    /**
     * @var \DB\SQL database class
     */
    public $db;

    /**
     * @var string table in the db
     */
    public $table;

    /**
     * @var string class name
     */
    public $mapperClass;

    /**
     * @var \FFCMS\Mappers\Mapper for class
     */
    public $mapper;


    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param \Log $logger
     * @param \DB\SQL $db
     */
    public function __construct(array $params = [], \Log $logger = null, \DB\SQL $db = null)
    {
        $f3 = \Base::instance();

        if (is_object($logger)) {
            \Registry::set('logger', $logger);
        }
        $this->oLog = \Registry::get('logger');

        if (is_object($db)) {
            \Registry::set('db', $db);
        }
        $this->db = \Registry::get('db');

        // guess the table name from the class name if not specified as a class member
        $class = strrchr(get_class($this), '\\');
        $class = \UTF::instance()->substr($class,1);
        if (empty($this->table)) {
            $table = $f3->snakecase($class);
        } else {
            $table = $this->table;
        }
        $this->table = $table;

        $mapperClass = "\FFCMS\Mappers\\" . $class;
        if (class_exists($mapperClass)) {
            $this->mapper = new $mapperClass;
            $this->mapperClass = $mapperClass;
        }

        foreach ($params as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Get the associated mapper for the table
     * @return type
     */
    public function &getMapper()
    {
        return $this->mapper;
    }
}
