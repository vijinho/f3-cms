<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin App Tokens CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Tokens extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/tokens/';


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

        $f3->set('results', $this->getListingResults($f3, new Mappers\OAuth2Tokens));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Apps') => 'admin_apps_list',
            _('Tokens') => 'admin_tokens_list',
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

        $f3->set('results', $this->getSearchResults($f3, new Mappers\OAuth2Tokens));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Apps') => 'admin_apps_list',
            _('Tokens') => 'admin_tokens_list',
            _('Search') => '',
        ]);

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

        $uuid = $f3->get('REQUEST.uuid');
        $oAuth2Model = Models\OAuth2::instance();
        $mapper = $oAuth2Model->getTokensMapper();
        $mapper->load(['uuid = ?', $uuid]);
        $data = $mapper->cast();

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Apps') => 'admin_apps_list',
            _('Tokens') => 'admin_tokens_list',
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
        $this->csrf('@admin_tokens_list');
        $this->redirectLoggedOutUser();

        $oAuth2Model = Models\OAuth2::instance();
        $mapper = $oAuth2Model->getTokensMapper();

        // filter input vars of request, set back into REQUEST
        $mapper->copyfrom($f3->get('REQUEST'));
        $data = $mapper->filter();
        $request = $f3->get('REQUEST');
        foreach ($data as $k => $v) {
            if (array_key_exists($k, $request)) {
                $f3->set('REQUEST.' . $k, $v);
            }
        }

        // check app name exists
        if (!$mapper->load(['LOWER(uuid) = LOWER(?)', $request['uuid']])) {
            $this->notify(_('The app does not exist!'), 'warning');
            $f3->reroute('@api_tokens');
            return;
        }

        // check required fields
        $mapper->copyfrom($f3->get('REQUEST'));
        $mapper->validationRequired([
            'expires',
            'scope',
        ]);

        // at this point the app can be validated
        if (true !== $mapper->validate()) {
            $this->notify(['info' => $mapper->validationErrors($mapper->validate(false))]);
            $f3->reroute('@api_tokens');
        }

        if ($mapper->save()) {
            $this->notify(_('The app token has been updated!'), 'success');

            $this->audit([
                'users_uuid' => $mapper->users_uuid,
                'event' => 'Token Updated',
                'new' => $data
            ]);
        } else {
            $this->notify(_('App token update failed!'), 'error');
        }

        $f3->reroute('@admin_tokens_list');
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
            return $f3->reroute('@admin_tokens_list');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\OAuth2Tokens;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The token no longer exists!'), 'error');
            return $f3->reroute('@admin_tokens_list');
        }

        $oldMapper = clone($mapper);
        $mapper->erase();
        $this->notify('Token deleted!', 'success');
        $this->audit([
            'users_uuid' => $oldMapper->users_uuid,
            'event' => 'Token Deleted',
            'old' => $oldMapper->cast(),
        ]);
        return $f3->reroute('@admin_tokens_list');
    }

}
