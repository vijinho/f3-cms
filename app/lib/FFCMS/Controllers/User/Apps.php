<?php

namespace FFCMS\Controllers\User;

use FFMVC\Helpers;
use FFCMS\{Controllers, Models, Mappers, Traits};

/**
 * Use Apps Controller Class.
 *
 * OAuth2 WWW Handler
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Apps extends Controllers\User\Base
{
    /**
     * User's own API applications
     *
     * @param \Base $f3
     * @return void
     */
    public function apps(\Base $f3)
    {
        $this->redirectLoggedOutUser();

        $oAuth2Model = Models\OAuth2::instance();

        // fetch the user's apps
        $f3->set('apps', $oAuth2Model->getUserApps($f3->get('uuid')));

        $f3->set('breadcrumbs', [
            _('My Account') => 'user',
            _('Apps') => 'api_apps',
        ]);

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('apps/apps.phtml');
    }


    /**
     * register app
     *
     * @param \Base $f3
     * @return void
     */
    public function appPost(\Base $f3)
    {
        $this->csrf('@api_apps');
        $this->redirectLoggedOutUser();

        $view = 'apps/apps.phtml';
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
        $db = \Registry::get('db');
        $m = clone $appsMapper;
        if ($m->load(['LOWER('.$db->quotekey('name').') = LOWER(?)', $m->name]) && null !== $m->client_id) {
            $this->notify(_('An app with that name is already in use!'), 'warning');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // check required fields
        $appsMapper->validationRequired([
            'name',
            'description',
            'callback_uri',
        ]);

        // at this point the app can be validated
        if (true !== $appsMapper->validate()) {
            $this->notify(['info' => $appsMapper->validationErrors($appsMapper->validate(false))]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $appsMapper->client_id = $appsMapper->setUUID('client_id');
        $appsMapper->client_secret = $appsMapper->setUUID('client_secret');
        $appsMapper->users_uuid = $f3->get('uuid');

        // admin group auto-approved
        $scopes = $f3->get('userScopes');
        $appsMapper->status = in_array('admin', $scopes) ? 'approved' : 'registered';

        if ($appsMapper->validateSave()) {
            $this->notify(_('Your new app has been registered!'), 'success');

            $this->audit([
                'users_uuid' => $appsMapper->users_uuid,
                'actor' => $appsMapper->client_id,
                'event' => 'App Registered',
                'new' => $data
            ]);
        } else {
            $this->notify(_('App registration failed!'), 'error');
        }

        $f3->reroute('@api_apps');
    }


    /**
     * register app
     *
     * @param \Base $f3
     * @return void
     */
    public function updateAppPost(\Base $f3)
    {
        $this->csrf('@user_api');
        $this->redirectLoggedOutUser();

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
        ]);

        // at this point the app can be validated
        if (true !== $appsMapper->validate()) {
            $this->notify(['info' => $appsMapper->validationErrors($appsMapper->validate(false))]);
            $f3->reroute('@api_apps');
        }

        if ($appsMapper->validateSave()) {
            $this->notify(_('Your app has been updated!'), 'success');

            $this->audit([
                'users_uuid' => $appsMapper->users_uuid,
                'actor' => $appsMapper->client_id,
                'event' => 'App Updated',
                'new' => $data
            ]);
        } else {
            $this->notify(_('App update failed!'), 'error');
        }

        $f3->reroute('@api_apps');
    }
}
