<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};

/**
 * Api ConfigData REST Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class ConfigData extends APIMapper
{
    /**
     * Perform a create/update of the an item, used by POST, PUT, PATCH
     *
     * @param \Base $f3
     * @param array $prohibitedFields
     * @return void
     */
    private function save(\Base $f3, array $prohibitedFields = [])
    {
        // do not allow request to define these fields:
        $data = $f3->get('REQUEST');
        foreach ($prohibitedFields as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        // load pre-existing value
        $db = \Registry::get('db');
        $m = $this->getMapper();

        // copy data and validate
        $oldMapper = clone($m);
        $m->copyfrom($data);
        $m->validationRequired([
            'key', 'value', 'type', 'rank'
        ]);

        $errors = $m->validate(false);
        if (true !== $errors) {
            foreach ($errors as $error) {
                $this->setOAuthError('invalid_request');
                $this->failure($error['field'], $error['rule']);
            }
        } else {
            // load original record, ovewrite
            if ($f3->get('VERB') == 'PUT') {
                $m->load(['uuid = ?', $data['uuid']]);
            } else {
                $m->load([$db->quotekey('key') . ' = ?', $data['key']]);
            }
            $m->copyfrom($data);

            // load in original data and then replace for save
            if (!$m->validateSave()) {
                $this->setOAuthError('invalid_request');
                $this->failure('error', 'Unable to update object.');
                return;
            }

            $this->audit([
                'event' => 'Config Updated via API',
                'old' => $oldMapper->cast(),
                'new' => $m->cast()
            ]);

            // return raw data for object?
            $adminView = $f3->get('isAdmin') && 'admin' == $f3->get('REQUEST.view');
            $this->data = $adminView ? $m->castFields($f3->get('REQUEST.fields')) : $m->exportArray($f3->get('REQUEST.fields'));
        }
    }


    /**
     * Update data
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function patch(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $params['id']);
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.key', $m->key);

        // these fields can't be modified
        return $this->save($f3, [
            'id', 'uuid'
        ]);
    }


    /**
     * Replace data
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function put(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $params['id']);
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.uuid', $m->uuid);

        // these fields can't be modified
        return $this->save($f3, [
            'id'
        ]);
    }


    /**
     * Create new data
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function post(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin) {
            return;
        }

        // this fields can't be modified
        $prohibitedFields = [
            'id', 'uuid'
        ];

        return $this->save($f3, $prohibitedFields);
    }

}
