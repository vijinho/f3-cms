<?php

namespace FFCMS\Controllers\User;

use FFMVC\Helpers;
use FFCMS\{Controllers, Models, Mappers, Traits};

/**
 * Forgot Password Website Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class ForgotPassword extends User
{
    /**
     * forgot password page 1
     *
     * @param \Base $f3
     * @return void
     */
    public function forgotPasswordStep1(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf();

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('forgot_password/forgot_password_step1.phtml');
    }


    /**
     * send email
     *
     * @param \Base $f3
     * @return void
     */
    public function forgotPasswordStep1Post(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf();
        $view = 'forgot_password/forgot_password_step1.phtml';

        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();
        $usersDataMapper = $usersModel->getDataMapper();

        $email = $f3->get('REQUEST.email');
        $v = Helpers\Validator::instance();

        $check = $v->validate(['email' => $email], [
            'email' => 'required|valid_email']
        );

        $usersMapper->load(['LOWER(email) = LOWER(?)', $email]);

        if (true !== $check) {
            $this->notify(_("You did not enter a valid email-address."), "warning");
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;

        } elseif (empty($usersMapper->uuid)) {
            $this->notify(_("No user exists for that email address."), "error");
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;

        } else {

            $uuid = $usersMapper->uuid;
            if (null == $usersMapper->uuid) {
                $msg = "User account not found for $uuid";
                throw new \FFCMS\Exception($msg);
            }

            // set user scopes
            $scopes = empty($usersMapper->scopes) ? [] : preg_split("/[\s,]+/", $usersMapper->scopes);
            if (!in_array('user', $scopes) || in_array($usersMapper->status, ['closed', 'suspended', 'cancelled'])) {
                $msg = sprintf("User %s %s denied login because account group is not in 'user' or account status is in 'closed,suspended,cancelled'.",
                        $usersMapper->firstname, $usersMapper->lastname);
                throw new \FFCMS\Exception($msg);
            }

                // generate a random code to email to the user for password reset
            $usersModel->saveKey([
                'users_uuid' => $usersMapper->uuid,
                'key' => 'forgot-password-code',
                'value' => Helpers\Str::random(8)
            ]);

            $this->audit([
                'users_uuid' => $usersMapper->uuid,
                'actor' => $usersMapper->email,
                'event' => 'User Forgot Password'
            ]);

                // set the email template variables
            $f3->set('templateData',
                array_merge($usersMapper->cast(), [
                'code' => $usersDataMapper->value,
                'url' => Helpers\Url::internal('@forgot_password_step2')
            ]));

                // setup PHPMailer object with templateData rendered in Body
            $f3->set('form', $f3->get('REQUEST'));
            $mail = Helpers\Mail::getPHPMailer([
                'To' => $email,
                'Subject' => "Password Reset Request",
                'Body' => \Markdown::instance()->convert(\View::instance()->render('email/forgot_password.md','text/html')),
                'AltBody' => \View::instance()->render('email/forgot_password.md','text/plain')
            ]);

            if ($mail->send()) {
                $this->notify(_("A password reset email has been sent."), "success");
            } else {
                $this->notify(_("There was a problem sending the email, please try again later."), "warning");
                $this->notify($mail->ErrorInfo, "error");
            }

            $f3->reroute('@index');
        }
    }

    /**
     * link followed in forgot password email
     *
     * @param \Base $f3
     * @return void
     */
    public function forgotPasswordStep2(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf();

        if ($f3->get('REQUEST.code')) {
            return $this->forgotPasswordStep2Post($f3);
        }

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('forgot_password/forgot_password_step2.phtml');
    }

    /**
     * link clicked on in forgot password reset email
     * Password reset code from email
     * posted data from forgot password reset code form
     *
     * @param \Base $f3
     * @return void
     */
    public function forgotPasswordStep2Post(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf();

        $db = \Registry::get('db');
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();
        $usersDataMapper = $usersModel->getDataMapper();

        $viewStep2 = 'forgot_password/forgot_password_step2.phtml';
        $viewStep3 = 'forgot_password/forgot_password_step3.phtml';

        // load in the forgot password reset code row
        $usersDataMapper->load([$db->quotekey('value')." = ? AND ".$db->quotekey('key')." = 'forgot-password-code'", $f3->get('REQUEST.code')]);
        if (null == $usersDataMapper->uuid) {
            $this->notify(_('Unknown password reset code!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($viewStep2);
            return;
        }

        // check that the user exists for the reset code
        $usersMapper->load(['uuid = ?', $usersDataMapper->users_uuid]);
        if (null == $usersDataMapper->uuid) {
            $this->notify(_('Unknown user for reset code!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($viewStep2);
            return;
        }

            // valid data, show the reset password form
        $this->notify(_("Password code is valid."), 'info');

        // set data for form
        $f3->set('REQUEST.code', $usersDataMapper->value);
        $f3->set('REQUEST.uuid', $usersMapper->uuid);
        $f3->set('uuid', $usersMapper->uuid);
        $f3->set('password_question', $usersMapper->password_question);

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render($viewStep3);
    }


    /**
     * posted data from password reset form
     *
     * @param \Base $f3
     * @return void
     */
    public function forgotPasswordStep3(\Base $f3)
    {
        $this->redirectLoggedInUser();
        $this->csrf();

        $db = \Registry::get('db');
        $usersModel = Models\Users::instance();
        $usersMapper = $usersModel->getMapper();
        $usersDataMapper = $usersModel->getDataMapper();

        $redirectUrlStep2 = $this->url('@forgot_password_step2', ['code' => $f3->get('REQUEST.code')]);

        // load in the forgot password reset code row
        $usersDataMapper->load([$db->quotekey('value')." = ? AND ".$db->quotekey('key')." = 'forgot-password-code'", $f3->get('REQUEST.code')]);
        if (null == $usersDataMapper->uuid) {
            $this->notify(_('Unknown password reset code!'), 'error');
            return $f3->reroute($redirectUrlStep2);
        }

        // check that the user exists for the reset code
        $uuid = $f3->get('REQUEST.uuid');
        $usersMapper->load(['uuid = ?', $uuid]);
        if (null == $usersMapper->uuid) {
            $this->notify(_('Unknown user for reset code!'), 'error');
            return $f3->reroute($redirectUrlStep2);
        }

        // check that the password hint answer is correct
        $password_answer = $f3->get('REQUEST.password_answer');
        if (empty($password_answer) || strtolower($password_answer) !== strtolower($usersMapper->password_answer)) {
            $this->notify(_('Incorrect password answer!  Please try again.'), 'warning');
            return $f3->reroute($redirectUrlStep2);
        }

        // is this a password change?  if so, check they match
        $password = $f3->get('REQUEST.password');
        $confirm_password = $f3->get('REQUEST.confirm_password');
        if (!empty($password) || !empty($confirm_password)) {
            if ($password !== $confirm_password) {
                $this->notify(_('Password and confirm password must match!'), 'warning');
                return $f3->reroute($redirectUrlStep2);
            } elseif (Helpers\Str::passwordVerify($usersMapper->password, $password)) {
                $this->notify(_('The new password and old password are the same!  Try logging in.'), 'warning');
                $usersDataMapper->erase();
                return $f3->reroute($this->url('@login', ['email' => $usersMapper->email]));
            }
        } elseif (empty($password) && empty($confirm_password)) {
            $this->notify(_('Password and confirm password missing!'), 'warning');
            return $f3->reroute($redirectUrlStep2);
        }

            // set new hashed password
        $oldPassword = $usersMapper->password;
        $newPassword = Helpers\Str::password($password);
        $usersMapper->password = $newPassword;

        if ($usersMapper->validateSave()) {
            $this->audit([
                'users_uuid' => $usersMapper->uuid,
                'actor' => $usersMapper->email,
                'event' => 'Password Update',
                'old' => $oldPassword,
                'new' => $newPassword,
            ]);
            $this->notify(_('Your password was updated!'), 'success');
        } else {
            $this->notify(_('Unable to update password!'), 'error');
            return $f3->reroute($redirectUrlStep2);
        }

        // remove password reset code now
        $usersDataMapper->erase();

        return $f3->reroute($this->url('@login', ['email' => $usersMapper->email]));
    }

}
