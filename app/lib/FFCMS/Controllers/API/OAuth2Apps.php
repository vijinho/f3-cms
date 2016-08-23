<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};

/**
 * Api OAuth2Apps REST Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class OAuth2Apps extends APIMapper
{
    protected $table = 'oauth2_apps';
    

    /**
     * Perform a create/update of the an item, used by POST, PUT, PATCH
     *
     * @param \Base $f3
     * @param array $params
     * @param array $prohibitedFields
     * @return void
     */
    private function save(\Base $f3, array $params, array $prohibitedFields = [])
    {
        $isAdmin = $f3->get('isAdmin');

        // set audit user if not set
        $data = $f3->get('REQUEST');
        $user = $f3->get('user');

        if (!array_key_exists('users_uuid', $data)) {
            $data['users_uuid'] = $user['uuid'];
        }

        if (!array_key_exists('status', $data)) {
            $data['status'] = 'approved';
        }

        if (!array_key_exists('client_id', $data)) {
            $data['client_id'] = Helpers\Str::uuid();
        }

        if (!array_key_exists('client_secret', $data)) {
            $data['client_secret'] = Helpers\Str::uuid();
        }

        // do not allow request to define these fields:
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
            'users_uuid', 'name'
        ]);

        $errors = $m->validate(false);
        if (true !== $errors) {
            foreach ($errors as $error) {
                $this->setOAuthError('invalid_request');
                $this->failure($error['field'], $error['rule']);
            }
        } else {
            // load original record, ovewrite
            if (!empty($data['uuid'])) {
                $m->load(['uuid = ?', $data['uuid']]);
            }
            $m->copyfrom($data);

            // load in original data and then replace for save
            if (!$m->validateSave()) {
                $this->setOAuthError('invalid_request');
                $this->failure('error', 'Unable to update object.');
                return;
            }

            $this->audit([
                'users_uuid' => $m->users_uuid,
                'actor' => $m->client_id,
                'event' => ' App Updated via API',
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
        $m = $this->getIdObjectIfAdmin($f3, $params, 'client_id', $params['id']);
        if (!is_object($m) || null == $m->client_id) {
            return;
        }

        $f3->set('REQUEST.client_id', $m->client_id);
        $f3->set('REQUEST.client_secret', $m->client_secret);

        // these fields can't be modified
        return $this->save($f3, $params, [
            'id'
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
        $m = $this->getIdObjectIfAdmin($f3, $params, 'client_id', $params['id']);
        if (!is_object($m) || null == $m->cilent_id) {
            return;
        }

        return $this->save($f3, $params, [
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

        return $this->save($f3, $params, $prohibitedFields);
    }

}
