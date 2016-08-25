<?php

namespace FFCMS\Models;

use FFCMS\{Traits, Mappers};

/**
 * Audit Model Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Audit extends DB
{

    /**
     * @var \FFCMS\Mappers\Audit  mapper for audit
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
        parent::__construct($params, $logger, $db);

        $this->mapper = new Mappers\Audit;
    }


    /**
     * Add a record to the audit table
     *
     * @param array $data
     * @return bool|array insert id or false if not enabled
     */
    public function write(array $data = [])
    {
        $f3 = \Base::instance();

        $enabled = !empty($f3->get('log.audit'));
        if (!$enabled) {
            return false;
        }

        // json_encode old, new, data or toString
        foreach (['old', 'new', 'data'] as $field) {
            if (array_key_exists($field, $data) && is_object($data[$field])) {
                $o = $data[$field];
                $rc = new \ReflectionClass(get_class($o));
                if ($rc->hasMethod('cast') || $rc->hasMethod('__toArray')) {
                    $o = $o->cast();
                } elseif ($rc->hasMethod('__toString')) {
                    $o = $o->__toString();
                } else {
                    $o = print_r($o,1);
                }
                $data['field'] = $o;
            }
        }

        // set 'new' to changed values
        if (array_key_exists('old', $data) && array_key_exists('new', $data) &&
            is_array($data['old']) && is_array($data['new'])) {
            $data['new'] = json_encode(array_diff_assoc($data['new'], $data['old']), JSON_PRETTY_PRINT);
            $data['old'] = json_encode($data['old'], JSON_PRETTY_PRINT);
        } else {
            if (array_key_exists('old', $data) && is_array($data['old'])) {
                $data['old'] = json_encode($data['old'], JSON_PRETTY_PRINT);
            }
            if (array_key_exists('new', $data) && is_array($data['new'])) {
                $data['new'] = json_encode($data['new'], JSON_PRETTY_PRINT);
            }
        }

        // set actor to logged in user if not set
        if (!array_key_exists('actor', $data) || empty($data['actor'])) {
            $data['actor'] = $f3->get('uuid');
        }

        // set users_uuid to logged in user if not set
        if (!array_key_exists('users_uuid', $data) || empty($data['users_uuid'])) {
            $data['users_uuid'] = $f3->get('uuid');
        }

        // set ip to audit
        $data['ip'] = $f3->get('IP');
        $data['agent'] = $f3->get('AGENT');

        $m = $this->getMapper();
        $m->copyFrom($data);
        $m->validateSave();
        $data['id'] = $m->get('_id');
        return $data;
    }
}
