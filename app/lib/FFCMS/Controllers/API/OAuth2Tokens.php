<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};

/**
 * Api OAuth2Tokens REST Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class OAuth2Tokens extends Mapper
{
    protected $table = 'oauth2_tokens';


    /**
     * Perform a create/update of the an item, used by POST, PUT, PATCH
     *
     * @param \Base $f3
     * @param array $prohibitedFields
     * @return void
     */
    private function save(\Base $f3, array $prohibitedFields = [])
    {
        // set audit user if not set
        $data = $f3->get('REQUEST');
        $user = $f3->get('user');

        if (!array_key_exists('users_uuid', $data)) {
            $data['users_uuid'] = $user['uuid'];
        }

        if (!array_key_exists('client_id', $data)) {
            $data['client_id'] = Helpers\Str::uuid(16);
        }

        if (!array_key_exists('token', $data)) {
            $data['token'] = Helpers\Str::uuid(16);
        }

        if (!array_key_exists('type', $data)) {
            $data['type'] = 'access_token';
        }

        if (!array_key_exists('scope', $data)) {
            $data['scope'] = 'read';
        }

        // do not allow request to define these fields:
        foreach ($prohibitedFields as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        // load pre-existing value
        $m = $this->getMapper();

        // copy data and validate
        $m->copyfrom($data);
        $m->validationRequired([
            'users_uuid'
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
            if (!$m->save()) {
                $this->setOAuthError('invalid_request');
                $this->failure('error', 'Unable to update object.');
                return;
            }

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
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $params['id']);
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.uuid', $m->uuid);

        $client_id = $f3->get('REQUEST.client_id');
        if (empty($client_id)) {
            $f3->set('REQUEST.client_id', $m->client_id);
        }

        // these fields can't be modified
        return $this->save($f3, [
            'id'
        ]);
    }

}
