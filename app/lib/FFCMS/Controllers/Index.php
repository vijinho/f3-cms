<?php

namespace FFCMS\Controllers;

/**
 * Index Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Index extends Base
{
    /**
     *
     *
     * @param \Base $f3
     * @return void
     */
    public function index(\Base $f3)
    {
        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('index/index.phtml');
    }
}
