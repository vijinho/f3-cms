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
        Traits\Logger,
        Traits\Audit,
        Traits\ControllerSecurity,
        Traits\Validation;

    /**
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function __construct(\Base $f3, array $params)
    {
        $f3 = \Base::instance();

        $this->oUrlHelper = Helpers\Url::instance();
        $this->oNotification = Helpers\Notifications::instance();
        $this->oLog = \Registry::get('logger');
        $this->oAudit = Models\Audit::instance();
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
        $usersDataMapper = $usersModel->getDataMapper();

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

        // fetch the user groups
        $user = $usersMapper->cast();
        $user['groups'] = empty($user['groups']) ? [] : preg_split("/[\s,]+/", $user['groups']);
        $user['api_enabled'] = (int) in_array('api', $user['groups']);
        $f3->set('user_groups', $user['groups']);
        $f3->set('is_admin', in_array('admin', $user['groups']));
        $f3->set('is_root', in_array('root', $user['groups']));

        // fetch addtional information for the user
        $usersData = $usersModel->getUserDetails($usersMapper->uuid, [
            'access_token',
            'refresh_token',
            'email_confirmed',
        ]);

        $f3->set('user', array_merge($user, $usersData));
    }

}
