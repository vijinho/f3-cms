<?php

namespace FFCMS\Controllers;

use FFMVC\Helpers;
use FFCMS\{Traits, Models};

/**
 * Base Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
abstract class Base
{
    use Traits\UrlHelper,
        Traits\Notification,
        Traits\SecurityController,
        Traits\Validation;

    /**
     * init
     */
    public function __construct()
    {
        $this->oUrlHelper = Helpers\Url::instance();
        $this->oNotification = Helpers\Notifications::instance();
    }


    /**
     * Add default scripts for displaying templates - override in controller to add more
     *
     * @return void
     * @see app/config/default.ini
     */
    protected function addScripts()
    {
        // no scripts to add, override me and set css and js
        $this->setScripts();
    }


    /**
     * Set the scripts to load in the templates
     *
     * @param array $css list of css to load as defined in config.ini [css]
     * @param array $js list of js to load as defined in config.ini [js]
     * @return void
     * @see app/config/default.ini
     */
    protected function setScripts(array $css = [], array $js = [])
    {
        $f3 = \Base::instance();
        $env = ('production' == $f3->get('app.env')) ? 'production' : 'dev';
        $scripts = [];
        $scripts['css']['autoload'] = $css;
        $scripts['js']['autoload'] = $js;
        foreach (['js', 'css'] as $type) {
            $scripts[$type]['autoload'] = array_merge($scripts[$type]['autoload'], $f3->get($type . '.autoload'));
            $scripts[$type]['scripts'] = $f3->get($type . '.' . $env);
            $scripts[$type]['load'] = array_intersect_key($scripts[$type]['scripts'], array_flip($scripts[$type]['autoload']));
            $f3->set($type . '.load', $scripts[$type]['load']);
        }
    }

    /**
     * Logout if not admin
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
     public function beforeRoute(\Base $f3, array $params)
    {
        // get logged in user info
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();

        // get the logged in user
        $uuid = $f3->get('uuid');
        if (empty($uuid)) {
            return;
        }
        $usersMapper->load(['uuid = ?', $uuid]);

        // invalid user, go to logout
        if (empty($usersMapper->uuid)) {
            $f3->clear('uuid');
            $f3->clear('SESSION');
            $f3->reroute('@logout');
            return;
        }

        // fetch the user scopes
        $user = $usersMapper->cast();
        $user['scopes'] = empty($user['scopes']) ? [] : preg_split("/[\s,]+/", $user['scopes']);
        $user['apiEnabled'] = (int) in_array('api', $user['scopes']);
        $f3->set('userScopes', $user['scopes']);
        $f3->set('isAdmin', in_array('admin', $user['scopes']));
        $f3->set('isRoot', in_array('root', $user['scopes']));

        // fetch addtional information for the user
        $usersData = $usersModel->getUserDetails($usersMapper->uuid, [
            'access_token',
            'refresh_token',
            'email_confirmed',
        ]);

        $f3->set('user', array_merge($user, $usersData));

        // add default css and js
        $this->addScripts();
    }

}
