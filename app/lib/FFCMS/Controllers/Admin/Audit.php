<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Audit CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Audit extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\ControllerMapper;

    protected $template_path = 'cms/admin/audit/';

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

        $f3->set('results', $this->getListingResults($f3, new Mappers\Audit));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Audit') => 'admin_audit_list',
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

        $f3->set('results', $this->getSearchResults($f3, new Mappers\Audit));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Audit') => 'admin_audit_list',
            _('Search') => '',
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
    public function view(\Base $f3)
    {
        $this->redirectLoggedOutUser();

        if (false == $f3->get('is_root')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $auditModel = Models\Audit::instance();
        $auditMapper = $auditModel->getMapper();

        $uuid = $f3->get('REQUEST.uuid');
        $auditMapper->load(['uuid = ?', $uuid]);

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Audit') => 'admin_audit_list',
            _('View') => 'admin_audit_list',
        ]);

        $f3->set('form', $auditMapper->cast());

        echo \View::instance()->render($this->template_path . 'view.phtml');
    }

}
