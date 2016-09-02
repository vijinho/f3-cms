<?php

namespace FFCMS\Controllers;

use FFCMS\{Mappers};


/**
 * Index Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Page extends Base
{
    protected $template_path = 'pages/';

    /**
     * Render a published page unless not published yet
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function page(\Base $f3, array $params = [])
    {
        if (empty($params['slug'])) {
            // 404
            echo 'page does not exist';
            die(404);
        }

        $page = new Mappers\Pages;
        $page->load(['slug = ?', $params['slug']]);

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

        echo \View::instance()->render($this->template_path . '/page.phtml');
    }
}
