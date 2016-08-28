<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Users Data CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class UsersData extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/usersdata/';


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function listing(\Base $f3)
    {
        $view = strtolower(trim(strip_tags($f3->get('REQUEST.view'))));
        $view = empty($view) ? 'list.phtml' : $view . '.phtml';
        $f3->set('REQUEST.view', $view);

        $f3->set('results', $this->getListingResults($f3, new Mappers\UsersData));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => 'admin_users_list',
            _('Data') => 'admin_usersdata_list',
        ]);

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render($this->template_path . $view);
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function search(\Base $f3)
    {
        $view = strtolower(trim(strip_tags($f3->get('REQUEST.view'))));
        $view = empty($view) ? 'list.phtml' : $view . '.phtml';
        $f3->set('REQUEST.view', $view);

        $f3->set('results', $this->getSearchResults($f3, new Mappers\UsersData));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => 'admin_users_list',
            _('Data') => 'admin_usersdata_list',
            _('Search') => '',
        ]);

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render($this->template_path . $view);
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function edit(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $uuid = $f3->get('REQUEST.uuid');
        $usersModel = Models\Users::instance();
        $mapper = $usersModel->getDataMapper();
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_users_lists');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_users_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Data') => $this->url('@admin_usersdata_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Edit') => '',
        ]);

        $f3->set('form', $mapper->cast());
        echo \View::instance()->render($this->template_path . 'edit.phtml');
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function editPost(\Base $f3)
    {
        $this->csrf('@admin_usersdata_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'edit.phtml';

        // get current user details
        $uuid = $f3->get('REQUEST.uuid');
        $usersModel = Models\Users::instance();
        $mapper = $usersModel->getDataMapper();
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_usersdata_list');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_users_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Data') => $this->url('@admin_usersdata_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Edit') => '',
        ]);

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'value'
        ];

        // check input data has values set for the above fields
        foreach ($fields as $k => $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }
        // then remove any input data fields that aren't in the above fields
        foreach ($data as $field => $v) {
            if (!array_key_exists($field, $data) || empty($data[$field])) {
                unset($data[$field]);
            }
        }

        // type check for filtering and validation
        $fRules = '';
        switch ($mapper->type) {
            case 'text':
            case 'textarea':
                $fRules = 'trim|sanitize_string';
                break;

            case 'html':
            case 'markdown':
            case 'ini':
            case 'yaml':
                // trust raw input!
                $data['value'] = $f3->get('REQUEST_UNCLEAN.value');
                break;

            case 'json':
                $data['value'] = $f3->get('REQUEST_UNCLEAN.value');
                break;

            case 'email':
                $fRules = 'sanitize_email';
                $vRules = 'valid_email';
                break;

            case 'url':
                $fRules = 'trim|sanitize_string';
                $vRules = 'valid_url';
                break;

            case 'numeric':
            case 'whole_number':
            case 'integer':
            case 'boolean':
            case 'float':
                $fRules = 'trim|sanitize_string';
                if ('float' == $mapper->type) {
                    $fRules .= '|sanitize_floats';
                } else {
                    $fRules = 'sanitize_numbers';
                }
                $vRules = $mapper->type;
                break;

            case 'date':
                $fRules = 'trim|sanitize_string';
                $vRules = $mapper->type;
                break;
        }

        if (!empty($fRules)) {
            $this->filterRules(['value' => $fRules]);
        }
        if (!empty($vRules)) {
            $this->validationRules(['value' => $vRules]);
            $errors = $this->validate(false, ['value' => $data['value']]);
            if (true !== $errors) {
                $this->notify(['warning' => $this->validationErrors($errors)]);
                $f3->set('form', $mapper->cast());
                echo \View::instance()->render($this->template_path . 'edit.phtml');
                return;
            }
        }

        // update required fields to check from ones which changed
        // validate the entered data
        $data['uuid'] = $uuid;
        $mapper->copyfrom($data);
        $mapper->validationRequired($fields);
        $errors = $mapper->validate(false);
        if (is_array($errors)) {
            $this->notify(['warning' => $mapper->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // no change, do nothing
        if ($mapper->changed()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_usersdata_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $data['uuid']]);
        $mapper->copyfrom($data);
        if ($mapper->save()) {
            $this->notify(_('The account data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update account data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_usersdata_search' . '?search=' . $mapper->uuid);
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function add(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $users_uuid = $f3->get('REQUEST.users_uuid');
        $usersModel = Models\Users::instance();
        $mapper = $usersModel->getDataMapper();

        $data = $mapper->cast();
        $data['users_uuid'] = $users_uuid;

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_users_search', [
                'search' => $users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Data') => $this->url('@admin_usersdata_search', [
                'search' => $users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Add') => '',
        ]);

        $f3->set('form', $data);
        echo \View::instance()->render($this->template_path . 'add.phtml');
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function addPost(\Base $f3)
    {
        $this->csrf('@admin_usersdata_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'add.phtml';

        $users_uuid = $f3->get('REQUEST.users_uuid');
        $usersModel = Models\Users::instance();
        $mapper = $usersModel->getDataMapper();

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_users_search', [
                'search' => $users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Data') => $this->url('@admin_usersdata_search', [
                'search' => $users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Add') => '',
        ]);

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'users_uuid', 'key', 'value', 'type'
        ];

        // check input data has values set for the above fields
        foreach ($fields as $k => $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }
        // then remove any input data fields that aren't in the above fields
        foreach ($data as $field => $v) {
            if (!in_array($field, $fields)) {
                unset($data[$field]);
            }
        }

        // update required fields to check from ones which changed
        // validate the entered data
        $data['users_uuid'] = $users_uuid;
        $mapper->copyfrom($data);
        $mapper->validationRequired($fields);
        $errors = $mapper->validate(false);
        if (is_array($errors)) {
            $this->notify(['warning' => $mapper->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // no change, do nothing
        if (!$mapper->changed()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_usersdata_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $mapper->uuid]);
        $mapper->copyfrom($data);
        if ($mapper->save()) {
            $this->notify(_('The account data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update account data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_usersdata_edit' . '?uuid=' . $mapper->uuid);
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function delete(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin_usersdata_list');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\UsersData;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The data item no longer exists!'), 'error');
            return $f3->reroute('@admin_usersdata_list');
        }

        $mapper->erase();
        $this->notify('User data deleted!', 'success');
        return $f3->reroute('@admin_usersdata_list');
    }

}
