<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Pages CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Pages extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/pages/';

    /**
     * Add default scripts for displaying templates
     *
     * @return void
     * @see app/config/default.ini
     */
    protected function addScripts()
    {
        // no scripts to add, override me and set css and js
        $this->setScripts([], ['showdown']);
    }


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

        $f3->set('results', $this->getListingResults($f3, new Mappers\Pages));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Pages') => 'admin_pages_list',
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

        $f3->set('results', $this->getSearchResults($f3, new Mappers\Pages));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Pages') => 'admin_pages_list',
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

        $mapper = new Mappers\Pages;
        $mapper->load(['uuid = ?', $uuid]);

        // fix date fields
        foreach (['published', 'expires', 'updated'] as $field) {
            if ('0000-00-00 00:00:00' == $mapper->$field) {
                $mapper->$field = null;
            }
        }

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_pages_list');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_pages_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Pages') => $this->url('@admin_pages_search', [
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
     * Set default values in page data where not set already
     * @param \Base $f3
     * @param \FFCMS\Mappers\Pages $mapper
     * @return $data array
     */
    protected function filterPageInput(\Base $f3, \FFCMS\Mappers\Pages &$mapper): array
    {
        // only allow updating of these fields
        $data = $f3->get('REQUEST');

        $fields = [
            'key',
            'author',
            'language',
            'status',
            'slug',
            'path',
            'keywords',
            'description',
            'title',
            'summary',
            'body',
            'scopes',
            'category',
            'tags',
            'metadata',
            'expires',
            'published',
            'robots',
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
        $data['users_uuid'] = $f3->get('uuid');

        // set default key
        if (empty($data['key'])) {
            $data['key'] = $data['title'];
        }

        // set default slug
        if (empty($data['slug'])) {
            $data['slug'] = $data['title'];
        }

        // url path
        if (empty($data['path'])) {
            $data['path'] = '/';
        }

        // publish status
        if (empty($data['status'])) {
            $data['status'] = 'draft';
        }

        // language
        if (empty($data['language'])) {
            $data['language'] = 'en';
        }

        // author
        if (empty($data['author'])) {
            $usersMapper = $f3->get('usersMapper');
            $data['author'] = $usersMapper->fullName();
        }

        // category
        if (empty($data['category'])) {
            $data['category'] = 'page';
        }

        // scope
        if (empty($data['scopes'])) {
            $data['scopes'] = 'public';
        }

        if (empty($data['scopes'])) {
            $data['scopes'] = 'public';
        }

        $mapper->copyfrom($data);
        $mapper->copyfrom($mapper->filter()); // filter mapa data
        $mapper->validationRequired([
            'title',
            'summary',
            'body',
        ]);

        return $mapper->cast();
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function editPost(\Base $f3)
    {
        $this->csrf('@admin_pages_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'edit.phtml';

        // get current user details
        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Pages;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_pages_list');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_pages_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Pages') => $this->url('@admin_pages_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Edit') => '',
        ]);

        $data = $this->filterPageInput($f3, $mapper);
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
            return $f3->reroute('@admin_pages_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $data['uuid']]);
        $mapper->copyfrom($data);
        if ($mapper->save()) {
            $this->notify(_('The page data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update page data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_pages_search' . '?search=' . $mapper->uuid);
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

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Pages;

        $data = $mapper->cast();
        $data['uuid'] = $uuid;

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Pages') => $this->url('@admin_pages_search', [
                'search' => $uuid,
                'search_fields' => 'uuid',
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
        $this->csrf('@admin_pages_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_pages_search', [
                'search' => $uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Pages') => $this->url('@admin_pages_search', [
                'search' => $uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Add') => '',
        ]);

        $view = $this->template_path . 'add.phtml';

        $mapper = new Mappers\Pages;
        $data = $this->filterPageInput($f3, $mapper);

        $errors = $mapper->validate(false);
        if (is_array($errors)) {
            $this->notify(['warning' => $mapper->validationErrors($errors)]);
            $f3->set('form', $data);
            echo \View::instance()->render($view);
            return;
        }

        // no change, do nothing
        if (!$mapper->changed()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_pages_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $mapper->uuid]);
        $mapper->copyfrom($data);
        if ($mapper->save()) {
            $this->notify(_('The page data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update page data!'), 'error');
            $f3->set('form', $data);
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_pages_search' . '?search=' . $mapper->uuid);
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function view(\Base $f3)
    {
        $this->redirectLoggedOutUser();

        $uuid = $f3->get('REQUEST.uuid');
        $view = strtolower(trim(strip_tags($f3->get('REQUEST.view'))));

        $mapper = new Mappers\Pages;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_pages_list');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_pages_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Pages') => $this->url('@admin_pages_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('View') => '',
        ]);

        $db = \Registry::get('db');
        $results = $db->exec($mapper->query);
        $f3->set('results', $results);

        $view = empty($view) ? 'view.phtml' : $view . '.phtml';
        $f3->set('REQUEST.view', $view);
        $f3->set('form', $mapper->cast());
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
        $this->csrf();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin_pages_list');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Pages;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The page no longer exists!'), 'error');
            return $f3->reroute('@admin_pages_list');
        }

        $mapper->erase();
        $this->notify('Page deleted!', 'success');
        $this->notify(_('Unable to update page data!'), 'error');
        return $f3->reroute('@admin_pages_list');
    }


}
