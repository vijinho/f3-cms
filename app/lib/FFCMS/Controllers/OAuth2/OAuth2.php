<?php

namespace FFCMS\Controllers\OAuth2;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};


/**
 * OAuth2 Controller Class.
 *
 * OAuth2 WWW Handler
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class OAuth2 extends Controllers\User\Base
{
    /**
     * OAuth2 Callback for testing app callback
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function Callback(\Base $f3, array $params)
    {
        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();

        // verify client_id is acceptable
        $clientId = $f3->get('REQUEST.client_id');
        if (empty($clientId)) {
            $this->notify(_('Invalid client id in request.'), 'warning');
            return $f3->reroute('@index');
        }

        // verify state - application must decide how really
        $state = $f3->get('REQUEST.state');
        if (empty($state)) {
            $this->notify(_('Invalid state in request.'), 'warning');
            return $f3->reroute('@index');
        }

        $state = $f3->get('REQUEST.state');
        if (empty($state)) {
            $this->notify(_('Invalid state in request.'), 'warning');
            return $f3->reroute('@index');
        }

        // there should be some scope
        $scope = $f3->get('REQUEST.scope');
        if (empty($scope)) {
            $this->notify(_('Invalid scope in request.'), 'warning');
            return $f3->reroute('@index');
        }


        // finally we need a code (8 digits) to swap for a token
        $code = (int) $f3->get('REQUEST.code');
        if (empty($code)) {
            $token = $f3->get('REQUEST.token');
        }
        if ($code < 9999999 && empty($token)) {
            $this->notify(_('Invalid code/token in request.'), 'warning');
            return $f3->reroute('@index');
        }

        // load the app to get the client_secret for the token call
        $appsMapper->load(['client_id = ?', $clientId]);
        if (empty($appsMapper->client_id)) {
            $this->notify(_('Unknown client_id in request.'), 'warning');
            return $f3->reroute('@index');
        }

        // get the url to retrieve the token
        if (!empty($code)) {
            $this->notify(_('Below is the URL to retrieve your token.'), 'info');
            // by now we have valid data from the request
            $url = $this->xurl(sprintf("http://%s/api/oauth2/token", $f3->get('HOST')), [
                'client_id' => $clientId,
                'client_secret' => $appsMapper->client_secret,
                'grant_type' => 'authorization_code',
                'code' => $code
            ]);
            $f3->set('retrieveTokenURL', $url);
        } elseif (!empty($token)) {
            $this->notify(_('Your access token is:') . ' ' . $token, 'info');
        }

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('oauth2/callback.phtml');
    }


    /**
     * Authenticate an incoming OAuth2 request for user access
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function Authenticate(\Base $f3, array $params)
    {
        $this->csrf('@user_api');

        $view = 'oauth2/authenticate.phtml';

        // redirect to user login if user not logged in
        $redirect_uri = $this->url($params[0], $f3->get('REQUEST'));

        $this->redirectLoggedOutUser('@login', [
            'redirect_uri' => $redirect_uri
        ]);

        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();
        $permissions = [];

        // assume there's problems!
        $f3->set('errors', true);

        // check valid fields
        $this->filterRules([
            'client_id' => 'trim|sanitize_string',
            'scope' => 'trim|sanitize_string',
            'state' => 'trim|sanitize_string',
            'response_type' => 'trim|sanitize_string',
            'redirect_uri' => 'trim|sanitize_string',
        ]);
        $request = $this->filter($f3->get('REQUEST'));
        foreach ($request as $k => $v) {
            $f3->set('REQUEST.' . $k, $v);
        }

        // check valid fields
        $this->validationRules([
            'client_id' => 'required|alpha_dash|exact_len,36',
            'scope' => 'required|min_len,3|max_len,4096',
            'state' => 'required|min_len,1|max_len,255',
            'response_type' => 'required|min_len,1|max_len,16',
            'redirect_uri' => 'valid_url',
        ]);
        $errors = $this->validate(false, $f3->get('REQUEST'));

        // if errors display form
        if (is_array($errors)) {
            $this->notify(['info' => $oAuth2Model->validationErrors($errors)]);
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

            // validate response_type - only one type is allowed anyway
        if ('code' !== $request['response_type']) {
            $request['response_type'] = 'token';
        }
        $f3->set('REQUEST.response_type', $request['response_type']);

            // validate scope(s)
        $allScopes = $oAuth2Model->SCOPES;
        $scopes = empty($request['scope']) ? [] : preg_split("/[\s,]+/", $request['scope']);

        foreach ($scopes as $k => $scope) {

            if (!array_key_exists($scope, $allScopes)) {
                $this->notify(_('Unknown scope specified ') . $scope, 'warning');
                unset($scopes[$k]);
            } else {
                $permissions[$scope] = $allScopes[$scope];
            }

        }

        // no valid scopes
        if (empty($scopes)) {
            $this->notify(_('No valid scope(s) specified'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // verify client id is valid
        $appsMapper->load(['client_id = ?', $request['client_id']]);
        if (empty($appsMapper->client_id)) {
            $this->notify(_('Unknown client id!'), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        // verify client app status
        if ('approved' !== $appsMapper->status) {
            $this->notify(sprintf(_('Application status %s currently forbids access.'), $appsMapper->status), 'error');
            $f3->set('form', $f3->get('REQUEST'));
            echo \View::instance()->render($view);
            return;
        }

        if (empty($request['redirect_uri'])) {
            $request['redirect_uri'] = $appsMapper->callback_uri;
        } elseif ($appsMapper->callback_uri !== $request['redirect_uri']) {
            $redirect_uris = empty($appsMapper->redirect_uris) ? [] : preg_split("/[\s,]+/", $appsMapper->redirect_uris);
            if (!in_array($request['redirect_uri'], $redirect_uris)) {
                $this->notify(_('Unregistered redirect_uri!'), $appsMapper->status, 'error');
                $f3->set('form', $f3->get('REQUEST'));
                echo \View::instance()->render($view);
                return;
            }
        }
        $f3->set('REQUEST.redirect_uri', $request['redirect_uri']);

        // verify client_id from session on accept/deny click
        $f3->set('SESSION.client_id', $appsMapper->client_id);

        // allowed scopes
        $f3->set('SESSION.scope', join(',', array_keys($permissions)));

        // validate client_id
        $client = true;

        if (!empty($client)) {
            // get client permissions requested
            // if valid, create code and access token for it

            $f3->set('confirmUrl',
                $this->url('@oauth_confirm', $f3->get('REQUEST')));

            $f3->set('denyUrl',
                $this->url('@oauth_deny',  $f3->get('REQUEST')));

            $f3->set('errors', false);
        }

        $f3->set('permissions', $permissions);

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render($view);
    }


    /**
     * Accept incoming OAuth2 request for user access
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function ConfirmPost(\Base $f3, array $params)
    {
        $this->csrf('@user_api');
        $request = $f3->get('REQUEST');

        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();
        $tokensMapper = $oAuth2Model->getTokensMapper();

        // verify client_id is acceptable
        $sessionClientId = $f3->get('SESSION.client_id');
        $clientId = $f3->get('REQUEST.client_id');
        if (empty($sessionClientId) || empty($clientId) || $clientId !== $sessionClientId) {
            $this->notify(_('Invalid client id in request.'), 'warning');
            return $f3->reroute('@index');
        }
        $f3->clear('SESSION.client_id');

        // verify client id is valid
        $appsMapper->load(['client_id = ?', $clientId]);
        if (empty($appsMapper->client_id)) {
            $this->notify(_('Unknown client id!'), 'error');
            return $f3->reroute('@index');
        }

        // get scopes
        $scope = $f3->get('SESSION.scope');
        if (empty($scope)) {
            $this->notify(_('Unknown scope!'), 'error');
            return $f3->reroute('@index');
        }
        $f3->clear('SESSION.scope');
        // generate a  new app user token
        // load pre-existing value
        $tokensMapper->load(['client_id = ? AND users_uuid = ?', $clientId, $f3->get('uuid')]);

        $response_type = $f3->get('REQUEST.response_type');
        switch ($response_type) {

            case 'token':
                // we already have a token, and the token is not a uuid
                if (null !== $tokensMapper->token && !is_numeric($tokensMapper->token)) {
                    break;
                }
                $data = [
                    'users_uuid' => $f3->get('uuid'),
                    'client_id' => $clientId,
                    'token' => null,
                    'type' => 'access_token',
                    'scope' => $scope
                ];
                $tokensMapper->copyfrom($data);
                $tokensMapper->token = $tokensMapper->setUUID('token');
                $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
                break;

            case 'code':
                $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_code'));
                break;

            default:
                $this->notify(_('Unknown response type!'), 'error');
                return $f3->reroute('@index');

        }

        if (!$tokensMapper->save()) {
            $this->notify(_('Could not create app access.'), 'error');
        } else {
            $this->notify(_('Access granted to') . ' ' . $appsMapper->name, 'success');

            $url = $request['redirect_uri'];
            $data = [
                'state' => $request['state'],
                'client_id' => $tokensMapper->client_id,
                'scope' => $tokensMapper->scope,
                $response_type => $tokensMapper->token
            ];

            // token must be sent in url fragment #
            if ($response_type == 'token') {
                unset($data[$response_type]);
            }
            $url = Helpers\Url::external($url, $data);
            if ($response_type == 'token') {
                $url .= '#token=' . $tokensMapper->token;
            }

            $f3->set('returnUrl', $url);
        }

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('oauth2/confirm.phtml');
    }


    /**
     * Deny incoming OAuth2 request for user access
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function Deny(\Base $f3, array $params)
    {
        $this->csrf('@user_api');
        $request = $f3->get('REQUEST');
        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();

        // verify client_id is acceptable
        $sessionClientId = $f3->get('SESSION.client_id');
        $clientId = $f3->get('REQUEST.client_id');
        if (empty($sessionClientId) || empty($clientId) || $clientId !== $sessionClientId) {
            $this->notify(_('Invalid client id in request.'), 'warning');
            return $f3->reroute('@index');
        }
        $f3->clear('SESSION.client_id');

        // get scopes
        $scope = $f3->get('SESSION.scope');
        if (empty($scope)) {
            $this->notify(_('Unknown scope!'), 'error');
            return $f3->reroute('@index');
        }
        $f3->clear('SESSION.scope');

        // verify client id is valid
        $appsMapper->load(['client_id = ?', $clientId]);
        if (empty($appsMapper->client_id)) {
            $this->notify(_('Unknown client id!'), 'error');
            return $f3->reroute('@index');
        }

        // link to redirect the user back with error description in the querystring
        $url = Helpers\Url::external($request['redirect_uri'], [
            'error' => 'access_denied',
            'error_description' => _('The user denied access to the application'),
            'state' => $request['state'],
            'client_id' => $appsMapper->client_id,
            'scope' => $scope,
        ]);

        $f3->set('denyUrl', $url);

        $f3->set('form', $f3->get('REQUEST'));
        echo \View::instance()->render('oauth2/deny.phtml');
    }

}
