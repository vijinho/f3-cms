<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Base Admin User Controller Class.
 *
 * Has methods common across user controllers
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Admin extends Controllers\User\Base
{
    /**
     * Logout if not admin
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function beforeRoute(\Base $f3, array $params)
    {
        parent::beforeRoute($f3, $params);

        // non-admin user gets logged out
        if (false == $f3->get('is_admin')) {
            $f3->reroute('@logout');
        }

        $this->redirectLoggedOutUser();
    }

    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function index(\Base $f3)
    {
        $this->redirectLoggedOutUser();

        echo \View::instance()->render('cms/admin/index.phtml');
    }


    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function phpinfo(\Base $f3)
    {
        phpinfo();
    }

}
