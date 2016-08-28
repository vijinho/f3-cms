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
    }

}
