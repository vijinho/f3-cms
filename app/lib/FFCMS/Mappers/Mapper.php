<?php

namespace FFCMS\Mappers;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Exceptions};

/**
 * Base Database Mapper Class extends f3's DB\SQL\Mapper
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @see https://fatfreeframework.com/cursor
 * @see https://fatfreeframework.com/sql-mapper
 * @see https://github.com/Wixel/GUMP
 */

// abstract class Magic implements ArrayAccess
// abstract class Cursor extends \Magic implements \IteratorAggregate
// class Mapper extends \DB\Cursor
/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $created
 */
abstract class Mapper extends \DB\SQL\Mapper
{
    use Traits\Validation;

    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     * all fields are visible except where 'false'
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
     * @var string $table for the mapper - this string gets automatically quoted
     */
    protected $table;

    /**
     * @var string $mapperName name for the mapper
     */
    protected $mapperName;

    /**
     * @var string $uuid the fieldname used for the uuid
     */
    protected $uuidField = 'uuid';

    /**
     * @var boolean $valid the data after validation is valid?
     */
    protected $valid = null;

    /**
     * @var array $originalData the original data when object created/loaded
     */
    protected $originalData = [];

    /**
     * @var array $auditData data to write to audit log
     */
    protected $auditData = [];

    /**
     * @var boolean $initValidation automatically append validation settings for fields $this->validationRules/Default?
     */
    protected $initValidation = true;

    /**
     * initialize with array of params
     *
     */
    public function __construct()
    {
        $f3 = \Base::instance();

        // guess the table name from the class name if not specified as a class member
        $class = \UTF::instance()->substr(strrchr(get_class($this), '\\'),1);
        $this->table = $this->mapperName = empty($this->table) ? $f3->snakecase($class) : $this->table;

        parent::__construct(\Registry::get('db'), $this->table);

        $this->initValidation(); // automatically create validation settings from reading tables
        $this->setupHooks(); // setup hooks for before/after data changes in mapper
    }

    /**
     * Load by internal integer ID or by UUID if no array passed or reload if null
     *
  	 * @param $filter string|array
 	 * @param $options array
 	 * @param $ttl int
     * @see DB\Cursor::load($filter = NULL, array $options = NULL, $ttl = 0)
     * @return array|FALSE
     */
    public function load($filter = NULL, array $options = null, $ttl = 0) {
        if (NULL === $filter && !empty($this->id)) {
            return $this->load($this->id);
        }
        if (!is_array($filter)) {
            if (is_int($filter)) {
                return parent::load(['id = ?', $filter], $options, $ttl);
            } else if (is_string($filter)) {
                return parent::load(['uuid = ?', $filter], $options, $ttl);
            }
        }
        return parent::load($filter, $options, $ttl);
    }

    /**
     * Initialise automatic validation settings for fields
     */
    public function initValidation()
    {
        if (!$this->initValidation) {
            return;
        }

        // work out default validation rules from schema and cache them
        $validationRules   = [];
        foreach ($this->schema() as $field => $metadata) {
            if ('id' == $field)  {
                continue;
            }

            $validationRules[$field] = '';
            $rules   = [];

            if (empty($metadata['nullable']) || !empty($metadata['pkey'])) {
                // special case, id for internal use so we don't interfere with this
                $rules[] = 'required';
            }

            if (preg_match('/^(?<type>[^(]+)\(?(?<length>[^)]+)?/i', $metadata['type'], $matches)) {
                switch ($matches['type']) {
                    case 'char':
                    case 'varchar':
                        $rules[] = 'max_len,' . $matches['length'];
                        break;

                    case 'text':
                        $rules[] = 'max_len,65535';
                        break;

                    case 'int':
                        $rules[] = 'integer|min_numeric,0';
                        break;

                    case 'datetime':
                        $rules[] = 'date|min_len,0|max_len,19';
                        break;

                    default:
                        break;
                }
                $validationRules[$field] = empty($rules) ? '' : join('|', $rules);
            }
        }

        // set default validation rules
        foreach ($this->validationRules as $field => $rule) {
            if (empty($rule)) {
                continue;
            }
            $validationRules[$field] = empty($validationRules[$field]) ? $rule :
                                                $validationRules[$field] .  '|' . $rule;
        }

        // save default validation rules and filter rules in-case we add rules
        $this->validationRulesDefault = $this->validationRules = $validationRules;
        $this->filterRulesDefault = $this->filterRules;
    }

    /**
     * Initialise hooks for the mapper object actions
     */
    public function setupHooks()
    {
        // set original data when object loaded
        $this->onload(function($mapper){
            $mapper->originalData = $mapper->cast();
        });

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
            if (in_array('updated', $mapper->fields())) {
                $mapper->updated = Helpers\Time::database();
            }
            return $mapper->validate();
        });

        // write audit data after save
        $this->aftersave(function($mapper){
            if ('audit' == $mapper->mapperName) {
                return;
            }
            $data = array_merge([
                'event' => (empty($mapper->originalData) ? 'created-'  : 'updated-') . $mapper->mapperName,
                'old' => $mapper->originalData,
                'new' => $mapper->cast()
            ], $this->auditData);
            Models\Audit::instance()->write($data);
            $mapper->originalData = $data['new'];
            $mapper->auditData = [];
        });

        // write audit data after erase
        $this->aftererase(function($mapper){
            if ('audit' == $mapper->mapperName) {
                return;
            }
            Models\Audit::instance()->write(array_merge([
                'event' => 'deleted-' . $mapper->mapperName,
                'old' => $mapper->originalData,
                'new' => $mapper->cast()
            ], $this->auditData));
            $mapper->originalData = $mapper->auditData = [];
        });
    }


    /**
     * Quote a database fieldname/key
     *
     * @param string $key String to quote as a database key
     * @param string $key
     */
    public function quotekey(string $key): string
    {
        if (!in_array($key, $this->fields())) {
            throw new Exceptions\InvalidArgumentException('No such key ' . $key . ' exists for mapper ' . $this->mapperName);
        }
        return $this->db->quotekey($key);
    }


    /**
     * Quote a database value
     *
     * @param mixed $value Value to quote
     * @param mixed $value
     */
    public function quote($value)
    {
        return $this->db->quote($value);
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
            if (array_key_exists($k, $this->fieldsVisible)) {
                unset($data[$k]);
                if (empty($this->fieldsVisible[$k])) {
                    continue;
                }
                // use the alias of the field
                $k = $this->fieldsVisible[$k];
                $data[$k] = $v;
            }

            // convert date to unix timestamp
            if ('updated' == $k || 'created' == $k || (
                    \UTF::instance()->strlen($v) == 19 &&
                    preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}[\s]+[\d]{2}:[\d]{2}:[\d]{2}/", $v, $m))) {
                $time = strtotime($v);
                $data[$k] = ($time < 0) ? 0 : $time;
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
     * @param int $len length of uuid to return
     * @return null|string $uuid the new uuid generated
     */
    public function setUUID(string $field = 'uuid', $len = 8)
    {
        // a proper uuid is actually 36 characters but we don't need so many
        if (in_array($field, $this->fields()) &&
            (empty($this->$field) || strlen($this->$field) < 36)) {
            $tmp = clone $this;

            do {
                $uuid = Helpers\Str::uuid($len);
            }
            while ($tmp->load([$this->quotekey($field) . ' = ?', $uuid]));

            unset($tmp);
            $this->$field = $uuid;
            return $uuid;
        }
        return empty($this->$field) ? null : $this->$field;
    }


    /**
     * Write data for audit logging
     *
     * @param $data array of data to audit log
     * @return array $this->auditData return the updated audit data for the mapper
     */
    public function audit(array $data = []): array
    {
        $this->auditData = array_merge($this->auditData, $data);
        return $this->auditData;
    }

}
