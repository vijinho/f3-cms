<?php

namespace FFCMS\Controllers\Admin;

use FFMVC\Helpers;
use FFCMS\{Traits, Controllers, Models, Mappers};

/**
 * Admin Users CMS Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Users extends Admin
{
    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    protected $template_path = 'cms/admin/users/';


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

        $f3->set('results', $this->getListingResults($f3, new Mappers\Users));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => 'admin_users_list',
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

        $f3->set('results', $this->getSearchResults($f3, new Mappers\Users));

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => 'admin_users_list',
            _('Search') => 'admin_users_search',
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
    public function edit(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $usersModel = Models\Users::instance();
        $uuid = $f3->get('REQUEST.uuid');
        $usersMapper = $usersModel->getUserByUUID($uuid);
        if (null == $usersMapper->id) {
            $this->notify(_('The account no longer exists!'), 'error');
            $f3->reroute('@admin_users_lists');
        }

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => 'admin_users_list',
            _('Edit') => '',
        ]);

        $f3->set('form', $usersMapper->cast());
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
        $this->csrf('@admin_users_list');
        $this->redirectLoggedOutUser();

        if (false == $f3->get('isRoot')) {
            $this->notify(_('You do not have (root) permission!'), 'error');
            return $f3->reroute('@admin');
        }

        $view = $this->template_path . 'edit.phtml';

        $f3->set('breadcrumbs', [
            _('Admin') => 'admin',
            _('Users') => 'admin_users_list',
            _('Edit') => '',
        ]);

        // get current user details
        $usersModel = Models\Users::instance();
        $uuid = $f3->get('REQUEST.uuid');
        $usersMapper = $usersModel->getUserByUUID($uuid);
        if (null == $usersMapper->id) {
            $this->notify(_('The account no longer exists!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render('user/account.phtml');
            return;
        }
        $oldUserMapper = clone $usersMapper;

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'email',
            'password',
            'firstname',
            'lastname',
            'password_question',
            'password_answer',
            'scopes',
            'status',
        ];

        // check input data has values set for the above fields
        foreach ($fields as $k => $field) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = null;
            }
        }
        // then remove any input data fields that aren't in the above fields
        foreach ($data as $field => $v) {
            if (!in_array($field, $fields)) {
                unset($data[$field]);
            }
        }

        // is this a password change?  if so, check they match
        $str = Helpers\Str::instance();
        $password = $f3->get('REQUEST.password');
        $confirm_password = $f3->get('REQUEST.confirm_password');
        if (!empty($password) || !empty($confirm_password)) {
            if ($password !== $confirm_password) {
                $this->notify(_('That password and confirm password must match!'), 'warning');
                $f3->set('form', $f3->get('REQUEST'));
                echo \View::instance()->render($view);
                return;
            } elseif ($str->passwordVerify($usersMapper->password, $password)) {
                $this->notify(_('The new password and old password are the same!'), 'warning');
                $f3->set('form', $f3->get('REQUEST'));
                echo \View::instance()->render($view);
                return;
            } else {
                // set new hashed password
                $data['password'] = $str->password($password);
            }
        } else {
            // same password
            $data['password'] = $usersMapper->password;
        }

        // check if email address change that email isn't taken
        $email = $f3->get('REQUEST.email');
        if ($usersMapper->email !== $email) {
            $usersMapper->load(['email = ?', $email]);
            if ($usersMapper->email == $email) {
                $this->notify(sprintf(_('The email address %s is already in use!'), $email), 'warning');
                $f3->set('form', $f3->get('REQUEST'));
                echo \View::instance()->render($view);
                return;
            } else {
                // new email
                $data['email'] = $email;
            }
        }

        // update required fields to check from ones which changed
        // validate the entered data
        $data['uuid'] = $uuid;
        $usersMapper->copyfrom($data);
        $usersMapper->validationRequired($fields);
        $errors = $usersMapper->validate(false);
        if (is_array($errors)) {
            $this->notify(['warning' => $usersMapper->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // no change, do nothing
        if ($usersMapper->cast() === $oldUserMapper->cast()) {
            $this->notify(_('There was nothing to change!'), 'info');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // reset usermapper and copy in valid data
        $usersMapper->load(['uuid = ?', $data['uuid']]);
        $usersMapper->copyfrom($data);
        if ($usersMapper->save()) {
            $this->audit([
                'users_uuid' => $usersMapper->uuid,
                'event' => 'User Updated',
                'old' => $oldUserMapper->cast(),
                'new' => $usersMapper->cast()
            ]);
            $this->notify(_('The account was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update your account!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        $f3->reroute('@admin_users_search' . '?search=' . $usersMapper->uuid);
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
            return $f3->reroute('@admin_users_list');
        }

        $uuid = $f3->get('REQUEST.uuid');

        $mapper = new Mappers\Users;
        $mapper->load(['uuid = ?', $uuid]);

        if (null == $mapper->id) {
            $this->notify(_('The user no longer exists!'), 'error');
            return $f3->reroute('@admin_users_list');
        }

        $oldMapper = clone($mapper);
        $mapper->erase();
        $this->notify('User deleted!', 'success');
        $this->audit([
            'users_uuid' => $oldMapper->uuid,
            'event' => 'User Deleted',
            'old' => $oldMapper->cast(),
        ]);
        return $f3->reroute('@admin_users_list');
    }

}
