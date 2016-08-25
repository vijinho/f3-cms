<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Reports CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Reports extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/reports/';


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

        $f3->set('results', $this->getListingResults($f3, new Mappers\Reports));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Reports') => 'admin_reports_list',
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

        $f3->set('results', $this->getSearchResults($f3, new Mappers\Reports));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Reports') => 'admin_reports_list',
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

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Reports;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_reports_lists');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_reports_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Reports') => $this->url('@admin_reports_search', [
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
        $this->csrf('@admin_reports_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'edit.phtml';

        // get current user details
        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Reports;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_reports_list');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_reports_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Reports') => $this->url('@admin_reports_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Edit') => '',
        ]);

        $oldMapper = clone $mapper;

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'users_uuid',
            'scopes',
            'key',
            'name',
            'description',
            'query',
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
        $data['uuid'] = $f3->get('REQUEST.uuid');
        $data['users_uuid'] = $f3->get('uuid');
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
        if ($mapper->cast() === $oldMapper->cast()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_reports_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $data['uuid']]);
        $mapper->copyfrom($data);
        if ($mapper->validateSave()) {
            $this->audit([
                'event' => 'Report Updated',
                'old' => $oldMapper->cast(),
                'new' => $mapper->cast()
            ]);
            $this->notify(_('The report data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update report data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_reports_search' . '?search=' . $mapper->uuid);
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

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Reports;

        $data = $mapper->cast();
        $data['uuid'] = $uuid;

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Reports') => $this->url('@admin_reports_search', [
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
        $this->csrf('@admin_reports_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'add.phtml';

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Reports;

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_reports_search', [
                'search' => $uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Reports') => $this->url('@admin_reports_search', [
                'search' => $uuid,
                'search_fields' => 'users_uuid',
                'order' => 'key',
                'type' => 'exact',
                ]),
            _('Add') => '',
        ]);

        $oldMapper = clone $mapper;
        $oldMapper->load(['users_uuid = ?', $uuid]);

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'scopes',
            'key',
            'name',
            'description',
            'query',
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
        if ($mapper->cast() === $oldMapper->cast()) {
            $this->notify(_('There was nothing to change!'), 'info');
            return $f3->reroute('@admin_reports_list');
        }

        // reset usermapper and copy in valid data
        $mapper->load(['uuid = ?', $mapper->uuid]);
        $mapper->copyfrom($data);
        if ($mapper->validateSave()) {
            $this->audit([
                'event' => 'Report Updated',
                'old' => $oldMapper->cast(),
                'new' => $mapper->cast()
            ]);
            $this->notify(_('The report data was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update report data!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_reports_search' . '?search=' . $mapper->uuid);
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

        $mapper = new Mappers\Reports;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The entry no longer exists!'), 'error');
            return $f3->reroute('@admin_reports_lists');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => $this->url('@admin_reports_search', [
                'search' => $mapper->users_uuid,
                'search_fields' => 'uuid',
                'type' => 'exact',
                ]),
            _('Reports') => $this->url('@admin_reports_search', [
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

        if ('csv' !== $view) {
            $view = empty($view) ? 'view.phtml' : $view . '.phtml';
            $f3->set('REQUEST.view', $view);
            $f3->set('form', $mapper->cast());
            echo \View::instance()->render($this->template_path . $view);
        } else {
            // write the csv file
            $file = realpath($f3->get('TEMP')) . '/' . date('Y-m-d') . '-' . $mapper->key  .  '.csv';
            if (!empty($results) && count($results) > 0) {
                $fp = fopen($file, 'w');
                fputcsv($fp, array_keys($results[0]));
                foreach ($results as $k => $fields) {
                    $values = array_values($fields);
                    fputcsv($fp, $values);
                }
                fclose($fp);
            }

            header('Content-Description: File Transfer');
            header('Content-type: application/csv; charset=' . $f3->get('ENCODING'));
            header("Content-Disposition: attachment; filename=" . basename($file));
            header('Content-Length: ' . filesize($file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($file);
        }
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
            return $f3->reroute('@admin_reports_list');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Reports;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The report no longer exists!'), 'error');
            return $f3->reroute('@admin_reports_list');
        }

        $oldMapper = clone($mapper);
        $mapper->erase();
        $this->notify('Report deleted!', 'success');
        $this->audit([
            'event' => 'Report Deleted',
            'old' => $oldMapper->cast(),
        ]);
        $this->notify(_('Unable to update report data!'), 'error');
        return $f3->reroute('@admin_reports_list');
    }


}
