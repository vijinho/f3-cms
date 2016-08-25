<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Config CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Config extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/config/';


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function listing(\Base $f3)
    {
        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = strtolower(trim(strip_tags($f3->get('REQUEST.view'))));
        $view = empty($view) ? 'list.phtml' : $view . '.phtml';
        $f3->set('REQUEST.view', $view);

        $f3->set('results', $this->getListingResults($f3, new Mappers\ConfigData));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Config') => 'admin_config_list',
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
        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = strtolower(trim(strip_tags($f3->get('REQUEST.view'))));
        $view = empty($view) ? 'list.phtml' : $view . '.phtml';
        $f3->set('REQUEST.view', $view);

        $f3->set('results', $this->getSearchResults($f3, new Mappers\ConfigData));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Config') => 'admin_config_list',
            _('Search') => 'admin_config_search',
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
    public function delete(\Base $f3)
    {
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin_config_list');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\ConfigData;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The config item no longer exists!'), 'error');
            return $f3->reroute('@admin_config_list');
        }

        $oldMapper = clone($mapper);
        $mapper->erase();
        $this->notify('Config item deleted!', 'success');
        $this->audit([
            'event' => 'Config Deleted',
            'old' => $oldMapper->cast(),
        ]);
        return $f3->reroute('@admin_config_list');
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

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $uuid = $f3->get('REQUEST.uuid');
        $mapper = new Mappers\ConfigData;

        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_config_list');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Config') => 'admin_config_list',
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
        $this->csrf('@admin_config_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'edit.phtml';

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Config') => 'admin_config_list',
            _('Edit') => '',
        ]);

        // get current user details
        $uuid = $f3->get('REQUEST.uuid');
        $mapper = new Mappers\ConfigData;

        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_config_list');
        }

        $oldMapper = clone $mapper;

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'value', 'options', 'description'
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

        // no change, do nothing
        if ($mapper->cast() === $oldMapper->cast()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_config_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $data['uuid']]);
        $mapper->copyfrom($data);
        if ($mapper->validateSave()) {
            $this->audit([
                'event' => 'Config Data Updated',
                'old' => $oldMapper->cast(),
                'new' => $mapper->cast()
            ]);
            $this->notify(_('The config data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update config data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_config_search' . '?search=' . $mapper->uuid);
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

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $users_uuid = $f3->get('REQUEST.users_uuid');
        $mapper = new Mappers\ConfigData;


        $data = $mapper->cast();
        $data['users_uuid'] = $users_uuid;

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Config') => 'admin_config_list',
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
        $this->csrf('@admin_config_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'add.phtml';

        $uuid = $f3->get('REQUEST.uuid');
        $mapper = new Mappers\ConfigData;

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Config') => 'admin_config_list',
            _('Add') => '',
        ]);

        $oldMapper = clone $mapper;
        $oldMapper->load(['uuid = ?', $uuid]);

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'key', 'type', 'options', 'description'
        ];

        // check input data has values set for the above fields
        foreach ($fields as $k => $field) {
            if (!array_key_exists($field, $data) || empty($data[$field])) {
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
        $data['uuid'] = $uuid;
        $mapper->copyfrom($data);
        $mapper->validationRequired([
            'key', 'type', 'description'
        ]);
        $errors = $mapper->validate(false);
        if (is_array($errors)) {
            $this->notify(['warning' => $mapper->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // no change, do nothing
        if ($mapper->cast() === $oldMapper->cast()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_config_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $mapper->uuid]);
        $mapper->copyfrom($data);
        if ($mapper->validateSave()) {
            $this->audit([
                'event' => 'Config Data Updated',
                'old' => $oldMapper->cast(),
                'new' => $mapper->cast()
            ]);
            $this->notify(_('The config data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update config data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_config_edit' . '?uuid=' . $mapper->uuid);
    }

}
