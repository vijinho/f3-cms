<?php

namespace FFCMS\Controllers;

use FFCMS\{Mappers};


/**
 * Contact Page Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Contact extends Base
{
    protected $template_path = 'pages/';

    /**
     * Make contact page
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function contact(\Base $f3, array $params = [])
    {
        $page = new Mappers\Pages;
        $page->load(['slug = ?', 'contact']);

        // conditions if page is viewable
        $publishTime = strtotime($page->published);
        $expireTime = strtotime($page->expires);
        $showPage = $f3->get('REQUEST.preview') || (
            'published' == $page->status &&
            'public' == $page->scopes &&
            'page' == $page->category &&
            time() > $publishTime &&
            (0 >= $expireTime || $expireTime > time())
        );

        if (!$showPage) {
            // 404
            echo 'page unavailable';
            die(404);
        }

        $f3->set('pagesMapper', $page);

        echo \View::instance()->render($this->template_path . '/contact.phtml');
    }

    /**
     * Contact page form post handler
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function contactPost(\Base $f3, array $params = [])
    {
        // handle form input, send email
        
        echo \View::instance()->render($this->template_path . '/contact.phtml');
    }

}
