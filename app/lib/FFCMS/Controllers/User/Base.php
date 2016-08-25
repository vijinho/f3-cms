<?php

namespace FFCMS\Controllers\User;

use FFMVC\Helpers;
use FFCMS\{Controllers, Models, Mappers, Traits};

/**
 * Base User Controller Class.
 *
 * Has methods common across user controllers
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
abstract class Base extends Controllers\Base
{
    use Traits\SecurityController;

    /**
     * perform a client logout
     *
     * @param \Base $f3
     */
    protected function doLogout(\Base $f3)
    {
        $uuid = $f3->get('uuid');
        $f3->clear('SESSION');
        $f3->clear('uuid');
        if (!empty($uuid)) {
            Models\Users::instance()->logout($uuid);
        }
    }


    /**
     * logout for api users
     *
     * @param \Base $f3
     * @return void
     */
    public function logout(\Base $f3)
    {
        $this->doLogout($f3);
        $this->notify(_('You are now logged out!'), 'success');
        $f3->reroute('@index');
    }


    /**
     * show login screen form
     *
     * @param \Base $f3
     * @return void
     */
    public function login(\Base $f3)
    {
        $this->dnsbl();
        $this->redirectLoggedInUser();
        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('user/login.phtml');
    }


    /**
     * Redirect the user to the url if logged in
     *
     * @param string $url
     * @param array $params
     * @return void
     */
    protected function redirectLoggedInUser(string $url = '@user', array $params = [])
    {
        $f3 = \Base::instance();

        if (empty($params)) {
            $params = $f3->get('REQUEST');
        }

        // do not redirect the php session name
        $session_name = session_name();

        if (array_key_exists($session_name, $params)) {
            unset($params[$session_name]);
        }

        if (!empty($f3->get('uuid'))) {
            $f3->reroute($this->url($url, $params));
        }
    }


    /**
     * Redirect the user to the url if logged out
     *
     * @param string $url
     * @param array $params
     * @return void
     */
    protected function redirectLoggedOutUser(string $url = '@login', array $params = [])
    {
        $f3 = \Base::instance();

        if (empty($params)) {
            $params = $f3->get('REQUEST');
        }

        // do not redirect the php session name
        $session_name = session_name();

        if (array_key_exists($session_name, $params)) {
            unset($params[$session_name]);
        }

        if (empty($f3->get('uuid'))) {
            $f3->reroute($this->url($url, $params));
        }
    }

}
