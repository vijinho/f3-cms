<?php

namespace FFCMS\Mappers;

use FFCMS\Traits;
use FFMVC\Helpers;

/**
 * Base Database Mapper Class extends f3's DB\SQL\Mapper
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link https://fatfreeframework.com/sql-mapper
 * @link https://github.com/Wixel/GUMP
 */

// abstract class Magic implements ArrayAccess
// abstract class Cursor extends \Magic implements \IteratorAggregate
// class Mapper extends \DB\Cursor
/**
 * @property string $key
 * @property string $value
 * @property string $created
 */
abstract class Mapper extends \DB\SQL\Mapper
{
    use Traits\Logger,
        Traits\Validation;

    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     */
    protected $fieldsVisible = [];

    /**
     * Fields that are editable to clients, boolean or string of visible field name
     *
     * @var array $fieldsEditable
     */
    protected $fieldsEditable = [];

    /**
     * @var object database class
     */
    protected $db;

    /**
     * @var string $table for the mapper
     */
    protected $table;

    /**
     * @var string $uuid the fieldname used for the uuid
     */
    protected $uuidField = 'uuid';

    /**
     * @var boolean $valid the data after validation is valid?
     */
    protected $valid = null;

    /**
     * initialize with array of params
     *
     */
    public function __construct(array $params = [])
    {
        $f3 = \Base::instance();

        $this->oLog = \Registry::get('logger');
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

        parent::__construct($this->db, $table);

        foreach ($params as $k => $v) {
            $this->$k = $v;
        }

        // save default validation rules and filter rules in-case we add rules
        $this->validationRulesDefault = $this->validationRules;
        $this->filterRulesDefault = $this->filterRules;

        // filter data, set UUID and date created before insert
        $this->beforeinsert(function($mapper){
            $mapper->setUUID($mapper->uuidField);
            $mapper->copyFrom($mapper->filter());
            if (in_array('created', $mapper->fields()) && empty($mapper->created)) {
                $mapper->created = Helpers\Time::database();
            }
            return $mapper->validate();
        });

        // filter data, set updated field if present before update
        $this->beforeupdate(function($mapper){
            $mapper->copyFrom($mapper->filter());
            return $mapper->validate();
        });

    }


    /**
     * return string representation of class - json of data
     *
     * @param string
     */
    public function __toString(): string
    {
        return json_encode($this->cast(), JSON_PRETTY_PRINT);
    }


    /**
     * return array representation of class - json of data
     *
     * @param array
     */
    public function __toArray(): array
    {
        return $this->cast();
    }

    /**
     * Cast the mapper data to an array using only provided fields
     *
     * @param mixed string|array fields to return in response
     * @param array optional data optional data to use instead of fields
     * @return array $data
     */
    public function castFields($fields = null, array $data = []): array
    {
        if (!empty($fields)) {
            if (is_string($fields)) {
                $fields = preg_split("/[\s,]+/", strtolower($fields));
            } else if (!is_array($fields)) {
                $fields = [];
            }
        }

        if (empty($data) || !is_array($data)) {
            $data = $this->cast();
        }

        if (empty($fields)) {
            $fields = array_keys($data);
        }

        // remove fields not in the list
        foreach ($data as $k => $v) {
            if (!in_array($k, $fields)) {
                unset($data[$k]);
            }
        }

        $data['object'] = $this->table;

        return $data;
    }

    /**
     * Cast the mapper data to an array and modify (for external clients typically)
     * using the visible fields and names for export, converting dates to unixtime
     * optionally pass in a comma (or space)-separated list of fields or an array of fields
     *
     * @param mixed string|array fields to return in response
     * @param array optional data optional data to use instead of fields
     * @return array $data
     */
    public function exportArray($fields = null, array $data = []): array
    {
        if (!empty($fields)) {
            if (is_string($fields)) {
                $fields = preg_split("/[\s,]+/", strtolower($fields));
            } else if (!is_array($fields)) {
                $fields = [];
            }
        }

        if (empty($data) || !is_array($data)) {
            $data = $this->cast();
        }

        foreach ($data as $k => $v) {
            if (empty($this->fieldsVisible[$k])) {
                unset($data[$k]);
                continue;
            } elseif (true !== $this->fieldsVisible[$k]) {
                unset($data[$k]);
                $k = $this->fieldsVisible[$k];
                $data[$k] = $v;
            }
            // convert date to unix timestamp
            if ('updated' == $k || 'created' == $k || (
                strlen($v) == 19 && preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}[\s]+[\d]{2}:[\d]{2}:[\d]{2}/", $v, $m))) {
                $time = strtotime($v);
                if ($time < 0) {
                    $time = 0;
                }
                $data[$k] = $time;
            }
            if (!empty($fields) && $k !== 'id' && $k !== 'object' && !in_array($k, $fields)) {
                unset($data[$k]);
            }
        }

        $data['object'] = $this->table;

        return $data;
    }


    /**
     * Convert the mapper object to format suitable for JSON
     *
     * @param boolean $unmodified cast as public (visible) data or raw db data?
     * @param mixed $fields optional string|array fields to include
     * @return string json-encoded data
     */
    public function exportJson(bool $unmodified = false, $fields = null): string
    {
        return json_encode(empty($unmodified) ? $this->castFields($fields) : $this->exportArray($fields), JSON_PRETTY_PRINT);
    }


    /**
     * Set a field (default named uuid) to a UUID value if one is not present.
     *
     * @param string $field the name of the field to check and set
     * @return null|string $uuid the new uuid generated
     */
    public function setUUID(string $field = 'uuid')
    {
        $db = \Registry::get('db');
        // a proper uuid is 36 characters
        if (in_array($field, $this->fields()) &&
            (empty($this->$field) || strlen($this->$field) < 36)) {
            $tmp = clone $this;

            do {
                $uuid = Helpers\Str::uuid();
            }
            while ($tmp->load([$db->quotekey($field) . ' = ?', $uuid]));

            unset($tmp);
            $this->$field = $uuid;
            return $uuid;
        }
        return empty($this->$field) ? null : $this->$field;
    }
}
