<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Apps CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Apps extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/apps/';


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

        $f3->set('results', $this->getListingResults($f3, new Mappers\OAuth2Apps));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Apps') => 'admin_apps_list',
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

        $f3->set('results', $this->getSearchResults($f3, new Mappers\OAuth2Apps));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Apps') => 'admin_apps_list',
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

        $client_id = $f3->get('REQUEST.client_id');
        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();
        $appsMapper->load(['client_id = ?', $client_id]);
        $data = $appsMapper->cast();

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Apps') => 'admin_apps_list',
            _('Edit') => '',
        ]);

        $f3->set('form', $data);
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
        $this->csrf('@admin_apps_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();

        // filter input vars of request, set back into REQUEST
        $appsMapper->copyfrom($f3->get('REQUEST'));
        $data = $appsMapper->filter();
        $request = $f3->get('REQUEST');
        foreach ($data as $k => $v) {
            if (array_key_exists($k, $request)) {
                $f3->set('REQUEST.' . $k, $v);
            }
        }

        // check app name exists
        if (!$appsMapper->load(['LOWER(client_id) = LOWER(?) AND users_uuid = ?',
                $request['client_id'], $f3->get('uuid')])) {
            $this->notify(_('The app does not exist!'), 'warning');
            $f3->reroute('@api_apps');
            return;
        }

        // check required fields
        $appsMapper->copyfrom($f3->get('REQUEST'));
        $appsMapper->validationRequired([
            'name',
            'description',
            'callback_uri',
            'status',
        ]);

        // at this point the app can be validated
        if (true !== $appsMapper->validate()) {
            $this->notify(['info' => $appsMapper->validationErrors($appsMapper->validate(false))]);
            $f3->reroute('@api_apps');
        }

        if ($appsMapper->validateSave()) {
            $this->notify(_('The app has been updated!'), 'success');

            $this->audit([
                'users_uuid' => $appsMapper->users_uuid,
                'actor' => $appsMapper->client_id,
                'event' => 'App Updated',
                'new' => $data
            ]);
        } else {
            $this->notify(_('App update failed!'), 'error');
        }

        $f3->reroute('@admin_apps_list');
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
            return $f3->reroute('@admin_apps_list');
        }

        $client_id = $f3->get('REQUEST.client_id');

        $mapper = new Mappers\OAuth2Apps;
        $mapper->load(['client_id = ?', $client_id]);

        $oldMapper = clone($mapper);
        $mapper->erase();
        $this->notify('App deleted!', 'success');
        $this->audit([
            'users_uuid' => $oldMapper->users_uuid,
            'event' => 'App Deleted',
            'old' => $oldMapper->cast(),
        ]);
        return $f3->reroute('@admin_apps_list');
    }

}
