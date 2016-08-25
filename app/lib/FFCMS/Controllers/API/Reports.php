<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};

/**
 * Api Reports REST Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Reports extends APIMapper
{
    protected $adminOnly = true;


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
        if ($f3->get('VERB') == 'PUT') {
            $m->load(['uuid = ?', $data['uuid']]);
        } else {
            $m->load(['users_uuid = ? AND ' . $db->quotekey('key') . ' = ?', $data['users_uuid'], $data['key']]);
        }

        // copy data and validate
        $oldMapper = clone($m);
        $m->copyfrom($data);
        $m->validationRequired([
            'users_uuid', 'key', 'name', 'query'
        ]);
        $errors = $m->validate(false);
        if (true !== $errors) {
            foreach ($errors as $error) {
                $this->setOAuthError('invalid_request');
                $this->failure($error['field'], $error['rule']);
            }
        } else {
            // load in original data and then replace for save
            if (!$m->validateSave()) {
                $this->setOAuthError('invalid_request');
                $this->failure('error', 'Unable to update object.');
                return;
            }

            $this->audit([
                'users_uuid' => $m->users_uuid,
                'actor' => $m->client_id,
                'event' => 'Report Updated via API',
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
     * @return null|array|boolean
     */
    public function patch(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $f3->get('id'));
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.users_uuid', $m->users_uuid);
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
     * @return null|array|boolean
     */
    public function put(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $f3->get('id'));
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.uuid', $m->uuid);
        $f3->set('REQUEST.users_uuid', $m->users_uuid);

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
     * @return null|array|boolean
     */
    public function post(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('isAdmin');
        if ($isAdmin && !empty($params) && array_key_exists('id', $params)) {
            $users_uuid = $params['id'];
        } elseif (!$isAdmin) {
            $users_uuid = $f3->get('uuid');
        } else {
            $users_uuid = $f3->get('REQUEST.users_uuid');
        }
        if (!$isAdmin) {
            return;
        }
        $f3->set('REQUEST.users_uuid', $users_uuid);

        // this fields can't be modified
        $prohibitedFields = [
            'id', 'uuid'
        ];

        return $this->save($f3, $prohibitedFields);
    }


}
