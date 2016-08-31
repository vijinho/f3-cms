<?php

namespace FFCMS\Controllers\User;

use FFMVC\Helpers;
use FFCMS\{Controllers, Models, Mappers, Traits, Enums};

/**
 * User Website Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class User extends Base
{

    /**
     * Add default scripts for displaying templates
     *
     * @return void
     * @see app/config/default.ini
     */
    protected function addScripts()
    {
        // no scripts to add, override me and set css and js
        $this->setScripts([], ['showdown']);
    }


    /**
     * user homepage
     *
     * @param \Base $f3
     * @return void
     */
    public function index(\Base $f3)
    {
        $this->redirectLoggedOutUser();

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('user/index.phtml');
    }


    /**
     * login form submitted
     *
     * @param \Base $f3
     * @return void
     */
    public function loginPost(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf('@user');

        // url if login failed
        $view = 'user/login.phtml';

        // filter input vars of request
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();
        $usersMapper->copyfrom($f3->get('REQUEST'));
        $data = $usersMapper->filter();
        $request = $f3->get('REQUEST');
        foreach ($data as $k => $v) {
            if (array_key_exists($k, $request)) {
                $f3->set('REQUEST.' . $k, $v);
            }
        }

        // find user by email address
        $usersMapper = $usersModel->getUserByEmail($f3->get('REQUEST.email'));
        if (null == $usersMapper->id) {
            $this->notify(_('No user found with that email!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // check the password is set
        $password = $f3->get('REQUEST.password');
        if (empty($password)) {
            $this->notify(_('You must enter a password!'), 'warning');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // verify password
        if (!Helpers\Str::passwordVerify($usersMapper->password, $password)) {
            $this->notify(_('Incorrect password!'), 'warning');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        if (!$usersModel->login()) {
            $this->notify(_('Unable to login!'), 'warning');
        } else {
            $f3->set('SESSION.uuid', $usersMapper->uuid);
            $f3->set('uuid', $usersMapper->uuid);
            $this->notify(_('You are now logged in!'), 'success');
            $uri = $f3->get('REQUEST.redirect_uri');
            return $f3->reroute(empty($uri) || !is_string($uri) ? '@user' : urldecode($uri));
        }
    }


    /**
     * my account
     *
     * @param \Base $f3
     * @return void
     */
    public function account(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        $f3->set('breadcrumbs', [
            _('My Account') => 'user',
            _('My Details') => 'account',
        ]);

        $f3->set('form', $f3->get('user'));
        echo \View::instance()->render('user/account.phtml');
    }


    /**
     * my account posted
     *
     * @param \Base $f3
     * @return void
     */
    public function accountPost(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        $view = 'user/account.phtml';
        $f3->set('breadcrumbs', [
            _('My Account') => 'user',
            _('My Details') => 'account',
        ]);

        // get current user details
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getUserByUUID($f3->get('uuid'));
        if (null == $usersMapper->id) {
            $this->notify(_('Your account no longer exists!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render('user/account.phtml');
            return;
        }

        // check password is correct
        $str = Helpers\Str::instance();
        $old_password = $f3->get('REQUEST.old_password');
        if (empty($old_password) || !$str->passwordVerify($usersMapper->password, $old_password)) {
            $this->notify(_('You entered your current password incorrectly!'), 'warning');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // only allow updating of these fields
        $data = $f3->get('REQUEST');
        $fields = [
            'email',
            'password',
            'firstname',
            'lastname',
            'password_question',
            'password_answer',
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
        } else {
            // no change
            unset($data['email']);
        }

        // update required fields to check from ones which changed
        // validate the entered data
        $data['uuid'] = $f3->get('uuid');
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
        if (!$usersMapper->changed()) {
            $this->notify(_('There was nothing to change!'), 'info');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // reset usermapper and copy in valid data
        $usersMapper->load(['uuid = ?', $data['uuid']]);
        $usersMapper->copyfrom($data);
        if ($usersMapper->save()) {
            $this->notify(_('Your account was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update your account!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // send verification email if email change - non-fatal
        if ($usersMapper->changed()) {
            // if email address changed, send confirmation enail
            if (!$usersModel->saveKey([
                'users_uuid' => $usersMapper->uuid,
                'key'       => 'email_confirmed',
                'value'     => 0
            ])) {
                $this->notify(_('Setting confirmation email failed.'), 'warning');
            }
            $this->sendConfirmationEmail();
        }

        $f3->reroute('@user');
    }


    /**
     * registration page
     *
     * @param \Base $f3
     * @return void
     */
    public function register(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf('@user');

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('user/register.phtml');
    }


    /**
     * registration posted
     *
     * @param \Base $f3
     * @return void
     */
    public function registerPost(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf('@register');

        // filter input vars of request
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();
        $usersMapper->copyfrom($f3->get('REQUEST'));
        $data = $usersMapper->filter();
        $request = $f3->get('REQUEST');
        foreach ($data as $k => $v) {
            if (array_key_exists($k, $request)) {
                $f3->set('REQUEST.' . $k, $v);
            }
        }

        $view = 'user/register.phtml';

        $email = $f3->get('REQUEST.email');
        if (empty($email)) {
            $this->notify(_('You need to enter an email address!'), 'warning');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // find user by email address
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getUserByEmail($email);
        if (null !== $usersMapper->id) {
            $this->notify(_('That user already exists!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // bad password
        $password = $f3->get('REQUEST.password');
        $confirm_password = $f3->get('REQUEST.confirm_password');
        if (empty($password) || empty($confirm_password) || ($password !== $confirm_password)) {
            $this->notify(_('That password and confirm password must match!'), 'warning');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // use the model to validate the data input
        $usersMapper->copyfrom($f3->get('REQUEST'));
        $usersMapper->validationRequired([
            'email',
            'password',
            'firstname',
            'lastname',
            'password_question',
            'password_answer',
        ]);

        // set defaults
        $usersMapper->setUUID();
        $usersMapper->scopes = 'user';
        $usersMapper->status = 'registered';
        $usersMapper->created = Helpers\Time::database();

        $errors = $usersMapper->validate(false);

        if (is_array($errors)) {
            $this->notify(['info' => $usersMapper->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        if (!$usersModel->register()) {
            $this->notify(_('Registration failed!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }
        $usersModel->login();
        $f3->set('SESSION.uuid', $usersMapper->uuid);
        $f3->set('uuid', $usersMapper->uuid);
        $this->notify(_('You successfully registered!'), 'success');
        $uri = $f3->get('REQUEST.redirect_uri');

        // send confirmation email
        $this->sendConfirmationEmail();

        return $f3->reroute(empty($uri) ? '@user' : urldecode($uri));
    }


    /**
     * Confirm email address link handler from email
     *
     * @param \Base $f3
     * @return void
     */
    public function confirmEmail(\Base $f3)
    {
        $db = \Registry::get('db');
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();
        $usersDataMapper = $usersModel->getDataMapper();

        // load in the forgot password reset code row
        $usersDataMapper->load([$db->quotekey('value')." = ? AND ".$db->quotekey('key')." = 'confirm_email_code'", $f3->get('REQUEST.code')]);
        if (null == $usersDataMapper->uuid) {
            $this->notify(_('Unknown password reset code!'), 'error');
            $f3->reroute('@index');
            return;
        }

        // check that the user exists for the reset code
        $usersMapper->load(['uuid = ?', $usersDataMapper->users_uuid]);
        if (null == $usersDataMapper->uuid) {
            $this->notify(_('Unknown user for confirmation code!'), 'error');
            $f3->reroute('@index');
            return;
        }

        // update account status to 'confirmed'
        $usersMapper->status = 'confirmed';
        if (!$usersMapper->save()) {
            $this->notify(_('Unable to update account status!'), 'error');
            $f3->reroute('@index');
            return;
        }

            //delete confirm_email_code and add email_confirmed
        $usersDataMapper->erase();
        $usersModel->saveKey([
            'users_uuid' => $usersMapper->uuid,
            'key'       => 'email_confirmed',
            'value'     => 1
        ]);

        $this->notify(_('Your email was successfully confirmed!'), 'success');

        $f3->reroute('@index');
    }


    /**
     * Send an email confirmation to the given user
     *
     * @param null|string $email the email address of the user
     * @param boolean true/false
     */
    private function sendConfirmationEmail(string $email = null)
    {
        $f3          = \Base::instance();
        $usersModel  = Models\Users::instance();
        $usersMapper = empty($email) ? $usersModel->getMapper() : $usersModel->getUserByEmail($email);
        $usersDataMapper = $usersModel->getDataMapper();
        if (empty($usersMapper->email)) {
            $this->notify(_('Could not send confirmation email.'), 'error');
            return;
        }

            // generate a random code to email to the user for confirming the email
        $usersModel->saveKey([
            'users_uuid' => $usersMapper->uuid,
            'key' => 'confirm_email_code',
            'value' => Helpers\Str::random(6)
        ]);

            // set the email template variables
        $f3->set('templateData',
            array_merge($usersMapper->cast(), [
            'code' => $usersDataMapper->value,
            'url' => Helpers\Url::internal('@confirm_email')
        ]));

        // setup PHPMailer
        $mail = Helpers\Mail::getPHPMailer([
            'To'      => $usersMapper->email,
            'Subject' => "Confirm Email Address",
            'Body'    => \Markdown::instance()->convert(\View::instance()->render('email/confirm_email.md')),
            'AltBody' => \View::instance()->render('email/forgot_password.md', 'text/plain')
        ]);

        if ($mail->send()) {
            $this->notify(_("A notification has been sent to confirm your email address."), "success");
        } else {
            $this->notify(_("There was a problem sending you a registration email, please check your email and/or try again later."), "warning");
            $this->notify($mail->ErrorInfo, "error");
        }
    }


    /**
     * my profile
     *
     * @param \Base $f3
     * @return void
     */
    public function profile(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        $f3->set('breadcrumbs', [
            _('My Account') => 'user',
            _('My Profile') => 'profile',
        ]);

        // fetch profile
        $usersModel = Models\Users::instance();
        $profileData = $usersModel->getProfile($f3->get('uuid'));
        $f3->set('form', $profileData);

        echo \View::instance()->render('user/profile.phtml');
    }


    /**
     * my profile posted
     *
     * @param \Base $f3
     * @return void
     */
    public function profilePost(\Base $f3)
    {
        $this->redirectLoggedOutUser();
        $this->csrf();

        $view = 'user/profile.phtml';
        $f3->set('breadcrumbs', [
            _('My Account') => 'user',
            _('My Profile') => 'profile',
        ]);

        // handle file upload
        // wrong upload form field name or
        // upload mime type is not image/* or
        // size > 4 MB upload limit
        $files = \Web::instance()->receive(function($metadata, $fieldname){
            return !(
                'profile' !== $fieldname ||
                'image/' !== substr($metadata['type'], 0, 6) ||
                $metadata['size'] > Enums\Bytes::MEGABYTE() * 4
            );
        }, true, true);

        // create new profile image
        if (!empty($files)) {
            foreach ($files as $file => $valid) {
                if (false === $valid) {
                    $this->notify(_("The file uploaded was not valid!"), 'error');
                } else {
                    $user = $f3->get('usersMapper');
                    if ($user->profileImageCreate($file)) {
                        $this->notify(_("Your profile picture was updated!"), 'success');
                    }
                }
                unlink($file);
            }
        }

        // get existing profile and merge with input
        $usersModel = Models\Users::instance();
        $profileEnum = new Enums\ProfileKeys;

        // merge profile keys and filter input
        $profileData = $this->filter(
            array_intersect_key(
                array_merge(
                    $usersModel->getProfile($f3->get('uuid')),
                    $f3->get('REQUEST')
                ),
                $profileEnum->values()
            ), [
            'nickname' => 'trim|sanitize_string',
            'bio' => 'trim|sanitize_string'
        ]);

        $errors = $this->validate(false, $profileData, [
            'nickname' => 'valid_name',
        ]);
        if (is_array($errors)) {
            $this->notify(['warning' => $this->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // save profile
        $usersModel->saveData($f3->get('uuid'), $profileData);

        // set form data
        $f3->set('form', $profileData);

        echo \View::instance()->render('user/profile.phtml');
    }
}
