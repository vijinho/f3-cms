<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};

/**
 * Api Token Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Token extends API
{
    /**
     * Handle incoming access token request and process
     * with appropriate method based on grant_type.parameter
     *
     * requires input of client_id and client_secret
     *
     * @param \Base $f3
     * @return void
     */
    public function token(\Base $f3)
    {
        switch ($f3->get('REQUEST.grant_type')) {

            case 'authorization_code': // exchange auth code for access token
                return $this->code($f3);

            case 'client_credentials': // client app gets a token for itself
                return $this->credentials($f3);

            case 'password': // client app passes end-user username and password to get token
                return $this->password($f3);

            case 'refresh_token': // refresh access token using refresh token
                return $this->refresh($f3);

            default:
                $this->failure('api_connnection_error', "Grant type should be one of: (authorization_code, client_credentials, password, refresh_token)", 400);
                $this->setOAuthError('unsupported_grant_type');
        }
    }


    /**
     * Handle grant_type=authorization_code.
     *
     * requires input of 'code'
     *
     * @param \Base $f3
     * @return void
     * @link http://tools.ietf.org/html/rfc6749
     */
    protected function code(\Base $f3)
    {
        // this requires a valid client_id/secret
        if (empty($this->validateAccess())) {
            return;
        }

        // not a valid app
        $app = $f3->get('api_app');
        if (empty($app)) {
            return;
        }

        // check valid code
        $code = (int) $f3->get('REQUEST.code');
        if ($code < 9999999) {
            $this->failure('authentication_error', "The code was was invalid.", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // fetch models
        $db = \Registry::get('db');
        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();
        $tokensMapper = $oAuth2Model->getTokensMapper();

        // get the app's authorized app token
        $tokensMapper->load(['client_id = ? AND users_uuid = ? AND token = ?',
                $appsMapper->client_id, $appsMapper->users_uuid, $code]);
        if (null == $tokensMapper->users_uuid) {
            $this->failure('authentication_error', "Could not find the user for the token.", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // code expired!
        if (time() > strtotime($tokensMapper->expires)) {
            $this->failure('authentication_error', "The token expired.", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // now set a token into the the same token object
        $tokensMapper->setUUID('token');
        $tokensMapper->type = 'access_token';
        $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
        $tokensMapper->validateSave();

        $this->audit([
            'users_uuid' => $tokensMapper->users_uuid,
            'actor' => $tokensMapper->client_id,
            'event' => 'Token Updated via API',
            'new' => $tokensMapper->cast()
        ]);

        // check if there's already a refresh token for the client/user combination
        $rTokensMapper = clone $tokensMapper;
        $rTokensMapper->load(['client_id = ? AND users_uuid = ? AND '.$db->quotekey('type').' = "refresh_token"',
                $appsMapper->client_id, $appsMapper->users_uuid]);

        // make a new refresh token if one doesn't exist
        if (null == $rTokensMapper->token) {
             // if not, create one
            $data = $tokensMapper->cast();
            unset($data['id']);
            unset($data['uuid']);
            unset($data['token']);
            $rTokensMapper->copyfrom($data);
            $rTokensMapper->setUUID('token');
            $rTokensMapper->type = 'refresh_token';
            $rTokensMapper->expires = null;
            $rTokensMapper->validateSave();
        }

        // all good - return the token!
        $this->params['headers']['Service'] = 'OAuth2 Client Access Token';
        $this->data += [
            'access_token' => $tokensMapper->token,
            'refresh_token' => $rTokensMapper->token,
            'scope' => $tokensMapper->scope,
            'token_type' => 'bearer',
            'expires_in' => $f3->get('oauth2.expires_token')
        ];
    }


    /**
     * Handle grant_type=client_credentials.
     *
     * As per RFC6749 - must NOT return a refresh_token!
     *
     *
     * @param \Base $f3
     * @return void
     * @link http://tools.ietf.org/html/rfc6749
     */
    protected function credentials(\Base $f3)
    {
        // this requires a valid client_id/secret
        if (empty($this->validateAccess())) {
            return;
        }

        // not a valid app
        $app = $f3->get('api_app');
        if (empty($app)) {
            $this->failure('authentication_error', "Invalid client credentials!", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        $db = \Registry::get('db');
        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();
        $tokensMapper = $oAuth2Model->getTokensMapper();

        // get the app's authorized app token if it exists
        $tokensMapper->load(['client_id = ? AND users_uuid = ? AND '.$db->quotekey('type').' = "access_token"',
                $appsMapper->client_id, $appsMapper->users_uuid]);
        if (null == $tokensMapper->users_uuid) {
                // make a new token (and refresh token)
            $tokensMapper->users_uuid = $appsMapper->users_uuid;
            $tokensMapper->client_id = $appsMapper->client_id;
            $tokensMapper->type = 'access_token';
            $tokensMapper->scope = 'read write';
            $tokensMapper->setUUID('token');
            $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
            $tokensMapper->validateSave();
        }

        // token expired!
        if (time() > strtotime($tokensMapper->expires)) {
            // as it's a client credentials login we can extend it though
            $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
            $tokensMapper->validateSave();
        }

        // check if there's already a refresh token for the client/user combination
        $rTokensMapper = clone $tokensMapper;
        $rTokensMapper->load(['client_id = ? AND users_uuid = ? AND '.$db->quotekey('type').' = "refresh_token"',
                $appsMapper->client_id, $appsMapper->users_uuid]);
        // if not, create one
        if (null == $rTokensMapper->token) {
            $data = $tokensMapper->cast();
            unset($data['id']);
            unset($data['uuid']);
            unset($data['token']);
            $rTokensMapper->copyfrom($data);
            $rTokensMapper->setUUID('token');
            $rTokensMapper->type = 'refresh_token';
            $rTokensMapper->expires = Helpers\Time::database(time() + 3600);
            $rTokensMapper->validateSave();
        }

        // all good - return the access token only
        $this->params['headers']['Service'] = 'OAuth2 Client Credentials Access Token';
        $this->data += [
            'access_token' => $tokensMapper->token,
            'refresh_token' => $rTokensMapper->token,
            'scope' => $tokensMapper->scope,
            'token_type' => 'bearer',
            'expires_in' => $f3->get('oauth2.expires_token')
        ];
    }


    /**
     * Handle grant_type=password.
     *
     * requires input of 'username' and 'password'
     *
     * @param \Base $f3
     * @return void
     * @link http://tools.ietf.org/html/rfc6749
     */
    protected function password(\Base $f3)
    {
        if ('http' == $f3->get('SCHEME') && !empty($f3->get('api.https'))) {
            $this->failure('api_connection_failure', "Connection only allowed via HTTPS!", 400);
            $this->setOAuthError('unauthorized_client');
            return;
        }

        // fetch models now
        $db = \Registry::get('db');
        $oAuth2Model = Models\OAuth2::instance();
        $appsMapper = $oAuth2Model->getAppsMapper();
        $tokensMapper = $oAuth2Model->getTokensMapper();
        $usersModel = Models\Users::instance();

        $clientId = $f3->get('REQUEST.client_id');
        if (empty($clientId)) {
            $this->failure('authentication_error', "Missing client_id.", 400);
            $this->setOAuthError('invalid_request');
            return;
        }
        $appsMapper->load(['client_id = ?', $clientId]);
        if (null == $appsMapper->client_id) {
            $this->failure('authentication_error', "Invalid client_id.", 401);
            $this->setOAuthError('invalid_credentials');
            return;
        }
        $f3->set('api_app', $appsMapper->cast());

        // not a valid app
        $app = $f3->get('api_app');
        if (empty($app)) {
            $this->failure('authentication_error', "Invalid client credentials!", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // check required params
        $username = $f3->get('REQUEST.username');
        $password = $f3->get('REQUEST.password');
        if (empty($username) || empty($password)) {
            $this->failure('authentication_error', "Missing either username or password.", 401);
            $this->setOAuthError('invalid_request');
            return;
        }

        // check user exists
        $usersMapper = $usersModel->getMapper();
        $passwordHash = Helpers\Str::password($password);
        $usersMapper->load(['email = ? AND password =?', $username, $passwordHash]);
        if (null == $usersMapper->uuid) {
            $this->failure('authentication_error', "No user matching those credentials.", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // get the app users authorized app token if it exists
        $tokensMapper->load(['client_id = ? AND users_uuid = ? AND '.$db->quotekey('type').' = "access_token"',
                $appsMapper->client_id, $usersMapper->uuid]);
        if (null == $tokensMapper->users_uuid) {
                // make a new token (and refresh token)
            $tokensMapper->users_uuid = $usersMapper->uuid;
            $tokensMapper->client_id = $appsMapper->client_id;
            $tokensMapper->type = 'access_token';
            $tokensMapper->scope = 'read write';
            $tokensMapper->setUUID('token');
            $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
            $tokensMapper->validateSave();
        }
        // token expired!
        if (time() > strtotime($tokensMapper->expires)) {
            // as it's a password login we can extend it though
            $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
            $tokensMapper->validateSave();
        }

        // check if there's already a refresh token for the client/user combination
        $rTokensMapper = clone $tokensMapper;
        $rTokensMapper->load(['client_id = ? AND users_uuid = ? AND '.$db->quotekey('type').' = "refresh_token"',
                $appsMapper->client_id, $usersMapper->uuid]);

        // create one otherwise
        if (null == $rTokensMapper->token) {
            $data = $tokensMapper->cast();
            unset($data['id']);
            unset($data['uuid']);
            unset($data['token']);
            $rTokensMapper->copyfrom($data);
            $rTokensMapper->setUUID('token');
            $rTokensMapper->type = 'refresh_token';
            $rTokensMapper->expires = null;
            $rTokensMapper->validateSave();
        }

        // all good - return the access token only because client_secret absent
        $this->params['headers']['Service'] = 'OAuth2 Password Access Token';
        $this->data += [
            'access_token' => $tokensMapper->token,
            'scope' => $tokensMapper->scope,
            'token_type' => 'bearer',
            'expires_in' => $f3->get('oauth2.expires_token')
        ];
    }


    /**
     * Revoke an access token, revoke?token=TOKEN GET.
     *
     * @param \Base $f3
     * @return void
     */
    public function revoke(\Base $f3)
    {
        if (empty($this->validateAccess())) {
            return;
        }

        // not a valid app
        $app = $f3->get('api_app');
        if (empty($app)) {
            $this->failure('authentication_error', "Invalid client credentials!", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // check required params
        $token = $f3->get('REQUEST.token');
        if (empty($token)) {
            $this->failure('authentication_error', "Missing access token!", 401);
            $this->setOAuthError('invalid_request');
            return;
        }

        // fetch models now
        $oAuth2Model = Models\OAuth2::instance();

        // check refresh token exists
        $tokensMapper = $oAuth2Model->getTokensMapper();
        $tokensMapper->load(['client_id = ? AND token = ?',
                $app['client_id'], $token]);

        // get the token type and user
        $usersUUID = $tokensMapper->users_uuid;
        $isAccessToken = $tokensMapper->type == 'access_token';
        $isRefreshToken = $tokensMapper->type == 'refresh_token';

        // no matching token found, error
        if (!$isAccessToken && !$isRefreshToken) {
            $this->failure('authentication_error', "Not matching token(s) found!", 401);
            $this->setOAuthError('invalid_request');
            return;
        }

        // revoke the token, delete it
        $revoked = [];
        $revoked[$tokensMapper->type][] = $tokensMapper->token;
        $tokensMapper->erase();

            // erase other matching tokens for app and user id
        if ($isRefreshToken) {
            $tokens = $tokensMapper->find(['client_id = ? AND users_uuid = ?',
                    $app['client_id'], $usersUUID]);
            foreach ($tokens as $t) {
                $revoked[$t->type][] = $t->token;
                $t->erase();
            }
        }

        $this->params['headers']['Service'] = 'OAuth2 Revoke Token';
        $this->data += [
            'revoked' => $revoked
        ];
    }


    /**
     * Handle grant_type=refresh_token.
     *
     * Request a new access_token using the refresh_token
     *
     * @param \Base $f3
     * @return void
     * @link http://tools.ietf.org/html/rfc6749
     */
    protected function refresh(\Base $f3)
    {
        // fetch models now
        $oAuth2Model = Models\OAuth2::instance();
        $tokensMapper = $oAuth2Model->getTokensMapper();
        $db = \Registry::get('db');

        // check required params
        $refreshToken = $f3->get('REQUEST.refresh_token');
        if (empty($refreshToken)) {
            $this->failure('authentication_error', "Missing refresh token!",400);
            $this->setOAuthError('invalid_request');
            return;
        }

        // if no client_id/secret,, fetch app information
        if (empty($this->validateAccess())) {
            // the spec says we need client_id and client_password to do this
            $this->failure('authentication_error', "Unable to authenticate client!", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // not a valid app
        $app = $f3->get('api_app');
        if (empty($app)) {
            $this->failure('authentication_error', "Invalid client credentials!", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }

        // get the app users authorized app
        $app = $f3->get('api_app');

        // check refresh token exists
        $tokensMapper->load(['client_id = ? AND token =? AND '.$db->quotekey('type').' = "refresh_token"',
                $app['client_id'], $refreshToken]);
        if (null == $tokensMapper->client_id) {
            $this->failure('authentication_error', "Could not find that refresh token!", 401);
            $this->setOAuthError('invalid_grant');
            return;
        }
        $refreshToken = $tokensMapper->cast();

        $tokensMapper->load(['client_id = ? AND users_uuid =? AND '.$db->quotekey('type').' = "access_token"',
                $app['client_id'], $refreshToken['users_uuid']]);

        // no-pre existing token, make one
        if (null == $tokensMapper->client_id) {
            $tokensMapper->users_uuid = $refreshToken['users_uuid'];
            $tokensMapper->client_id = $app['client_id'];
            $tokensMapper->type = 'access_token';
            $tokensMapper->scope = $refreshToken['scope'];
        }

        // create a new token value
        $tokensMapper->setUUID('token');
        $tokensMapper->expires = Helpers\Time::database(time() + $f3->get('oauth2.expires_token'));
        $tokensMapper->validateSave();

        $this->params['headers']['Service'] = 'OAuth2 Refresh Access Token';
        $this->data += [
            'access_token' => $tokensMapper->token,
            'scope' => $tokensMapper->scope,
            'token_type' => 'bearer',
            'expires_in' => $f3->get('oauth2.expires_token')
        ];
    }

}
