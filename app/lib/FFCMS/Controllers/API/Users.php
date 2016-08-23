<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};

/**
 * Api Users REST Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Users extends APIMapper
{
    protected $adminOnly = false;


    /**
     * Display the authorised user or
     * if an admin, the user specified in the url /@id or  param ?id=
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function get(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('is_admin');
        $m = $this->getIdObjectIfUser($f3, $params, 'uuid', $f3->get('uuid'));
        if (!is_object($m) || null == $m->uuid) {
            return;
        } elseif (!$isAdmin && $m->uuid !== $f3->get('uuid')) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }
        // return raw data for object?
        $adminView = $f3->get('is_admin') && 'admin' == $f3->get('REQUEST.view');
        $this->data = $adminView ? $m->castFields($f3->get('REQUEST.fields')) : $m->exportArray($f3->get('REQUEST.fields'));
    }


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

        if (!empty($data['password'])) {
            $data['password'] = Helpers\Str::password($data['password']);
        }
        $f3->set('REQUEST', $data); // update REQUEST with prohibited fields removed

        // set validation check
        $m = $this->getMapper();
        $oldMapper = clone($m);
        $m->copyfrom($data);
        $m->validationRequired();
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
                'users_uuid' => $m->uuid,
                'actor' => $f3->get('uuid'),
                'event' => 'User Updated via API',
                'old' => $oldMapper->cast(),
                'new' => $m->cast()
            ]);

            // return raw data for object?
            $adminView = $f3->get('is_admin') && 'admin' == $f3->get('REQUEST.view');
            $this->data = $adminView ? $m->castFields($f3->get('REQUEST.fields')) : $m->exportArray($f3->get('REQUEST.fields'));
        }
    }


    /**
     * Update user details - normal user can
     *
     * @param \Base $f3
     * @return void
     */
    public function patch(\Base $f3)
    {
        $isAdmin = $f3->get('is_admin');
        // should return a pre-existing object
        $m = $this->getIdObjectIfUser($f3, $params, 'uuid', $f3->get('uuid'));
        if (!is_object($m) || null == $m->uuid) {
            return;
        } elseif (!$isAdmin && $m->uuid !== $f3->get('uuid')) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }

        // these fields can't be modified
        $fields = [
            'id', 'uuid', 'created', 'login_last', 'login_count'
        ];

        if (!$isAdmin) {
            $fields[] = 'status';
            $fields[] = 'groups';
        }

        return $this->save($f3, $fields);
    }


    /**
     * Replace user details - admin only
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function put(\Base $f3, array $params)
    {
        // should return a pre-existing object
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $f3->get('uuid'));
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        // these fields can't be modified
        $prohibitedFields = [
            'id', 'uuid', 'created'
        ];

        // clear all object fields except the above
        foreach ($m->fields() as $field) {
            if (!in_array($field, $prohibitedFields)) {
                $m->$field = null;
            }
        }

        return $this->save($f3, $prohibitedFields);
    }


    /**
     * Create a new user - admin only
     *
     * @param \Base $f3
     * @return void
     */
    public function post(\Base $f3)
    {
        // must be an admin
        $isAdmin = $f3->get('is_admin');
        if (!$isAdmin) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }

        // populate mapper with acceptable data for creating a new user
        $usersModel = Models\Users::instance();
        $this->mapper = $usersModel->newUserTemplate();

        // this fields can't be modified
        $prohibitedFields = [
            'id'
        ];

        return $this->save($f3, $prohibitedFields);
    }


    /**
     * Mark a user as status=closed
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function delete(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $f3->get('REQUEST.id'));
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        if ($f3->get('uuid') == $m->uuid) {
            $this->failure('client_error', "User cannot delete themself!", 401);
            return $this->setOAuthError('access_denied');

        }

        $m->status = 'closed';
        if ($m->validateSave()) {
            $deleted = true;
        } else {
            $deleted = false;
        }
        $this->data = [
            'deleted' => $deleted
        ];
    }

}
