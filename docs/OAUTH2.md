# Authentication Using OAuth 2.0

## What is OAuth?

OAuth is an open security protocol designed to protect system and user credentials in client applications. Prior to implementing OAuth, the API required client applications to provide both client and user credentials. The Getty Images API authenticated the credentials and then granted access to the API and user functionality such as search, download, and lightbox management. OAuth allows users to authorize a client application to access user functionality without requiring the client application to directly handle the user’s credentials. Thus OAuth improves security by reducing the exposure of end user credentials.

Please familiarize yourself with [RFC-6749](https://tools.ietf.org/html/rfc6749) for a full description of OAuth2.

## Important Terminology

Before we dive into the details of the OAuth 2.0 authorization workflows, let’s make sure we’re using a common vocabulary:

- *Resource Owner* -	The end user who has access to a set of resources.	An end-user.
- *Protected Resource* - Resource, stored on or provided by a server, that requires authorization to access it.
- *Authorization Server* - Entity that protects resources and validates credentials before authorizing a client application to take any action on behalf of an end user.
- *Client Application* - User registered application using the API.
- *Client Credentials* - Assigned client_id and client_secret for the client application.
- *Access Token* - Token provided by the authorization server to the client application to authorize access to resources.
- *Token Revocation* - Means of revoking an access token. An API user can revoke an access token if suspicious activity is detected.
- *Client Type* - A client type is assigned to a client application based on their ability to authenticate securely with the authorization server.

## Authorization Grant Flows

Summarized below are the four authorization grant flows in OAuth 2.0.

- *Implicit Grant*. Client-side application, where the application cannot secure the API secret. Requires: **client_id**, **email** and **password** of the user.
- *Resource Owner Password Credentials*.  Resource owner has high degree of trust with the client application. Requires: **email** and **password** of the user.
- *Client Credentials*. Client application is also the resource owner. Requires: **client_id** and **client_secret**.
- *Authorization Code*. Hosted web application, where client credentials are stored on the web server. Requires: **client_id**, **client_secret**, **email**, **password**.

## General Notes

### Scopes

A space-delimited list of scopes. If not provided, scope defaults to an empty list for users that
have not authorized any scopes for the application. For users who have authorized scopes for the
application, the user won't be shown the OAuth authorization page with the list of scopes.
Instead, this step of the flow will automatically complete with the set of scopes the user has
authorized for the application. For example, if a user has already performed the web flow twice
and has authorized one token with user scope and another token with repo scope, a third web flow
that does not provide a scope will receive a token with user and repo scope.

### Redirect URLs

The `redirect_uri` parameter is optional. If left out, we will redirect users to the callback URL configured in the OAuth Application settings. If provided, the redirect URL's host and port must exactly match the callback URL. The redirect URL's path must reference a subdirectory of the callback URL.

`CALLBACK: http://example.com/path`

* GOOD: http://example.com/path
* GOOD: http://example.com/path/subdir/other
* BAD:  http://example.com/bar
* BAD:  http://example.com/
* BAD:  http://example.com:8080/path
* BAD:  http://oauth.example.com:8080/path
* BAD:  http://example.org


## Making requests

Examples are using command-line [curl](http://curl.haxx.se/docs/manpage.html):

Replace `email` and `password` with your registered application's `client_id` and `client_secret` if you are not interacting with your own user account data and are requesting access on behalf of another user.

### Authentication Methods

While the API provides multiple methods for authentication, you SHOULD use OAuth for production applications.
The other methods provided are intended to be used for scripts or testing (i.e., cases where full OAuth would be overkill). Third party applications that rely on us for authentication should not ask for or collect credentials. Instead, they should use the OAuth web flow.

#### Via Bearer Token

When accessing the REST API, the application uses the bearer token to authenticate. Once you have an access token, as per [RFC-6750](https://tools.ietf.org/html/rfc6750), you can use it in a request in any of the following ways (listed from most to least desirable):
The bearer token may be used to issue requests to API endpoints which support application-only auth.
To use the bearer token, construct a normal HTTPS request and send the bearer token:

1. Send it in a request header: `Authorization: Bearer {access_token}`
2. Include it in a (application/x-www-form-urlencoded) POST body as `access_token={access_token}`
3. Put in the query string of a non-POST: `?access_token={access_token}`

Test token with curl:

```
curl -k -H "Authorization: Bearer {access_token}" https://{hostname}/api/v1/users
```

## Access tokens

We support [RFC-6749](https://tools.ietf.org/html/rfc6749)'s grant flows where `grant_type=`

- 'authorization_code' used to provide a code to a 3rd-party app and for it to then request a token with it
- 'password' used to request a token logging in with a registered user's email and password as 'username' and 'password' params
- 'client_credentials' are a client app requesting a token for itself
- 'refresh_token' is used to replace an access_token with a new one

to obtain access tokens through the following URL's:

- `https://{hostname}/oauth2/authorize`
- `https://{hostname}/api/v1/oauth2/token`

### Client Credentials Grant

The application gets a token for itself to make requests.

```
curl -k -X POST -u "{client_id}:{client_secret}" \
  https://{hostname}/api/v1/oauth2/token \
  -d grant_type=client_credentials
```

By default, the response will take the following form (this is fetching the token from the code given in the previous step)

```
{
    "access_token": "fb05359d-5428-3762-be25-1139dab585de",
    "refresh_token": "94ef078c-6031-3906-a22b-95ef368713f7",
    "scope": "read write",
    "token_type": "bearer",
    "expires_in": 86400
}
```


### Password Credentials Grant

This allows the application to get a token on behalf of a client of the user, sending their email (as username) and password as per [RFC6749 Section 4.3](http://tools.ietf.org/html/rfc6749#section-4.3)
Since this obviously requires the application to collect the user's password, it should only be used by apps created by the service itself.
Note, the client secret is not included here under the assumption that most of the use cases for password grants will be mobile or desktop apps, where the secret cannot be protected.

```
curl -k -X POST \
  https://{hostname}/api/v1/oauth2/token \
  -d grant_type=password -d client_id ={client_id} \
  -d username={email} -d password={password} -d scope='read write'
```


### Authorization Code Grant

The full-blown 3-LO flow. Request authorization from the end user by sending the registered user to:

```
https://{hostname}/oauth2/authorize?client_id={client_id}&response_type=code&scope={scope}&state={state}&redirect_uri={uri}
```

- the `client_id` is the id of the application requiring user access which was previously registered with us
- The `scope` {scope} is one of either 'read' 'write' or both separated by a space
- The scope attribute lists scopes attached to the token that were granted by the user. Normally, these scopes will be identical to what you requested
- `state` {state} is the state id of the application making the request to the user. An unguessable random string. It is used to protect against cross-site request forgery attacks.
- If the redirect/callback {uri} is empty, the user will be given the code directly to enter into your application.
- The callback includes the ?code={} query parameter, a numeric code that you can swap for an access token withing an hour of the resource owner (user) accepting the request:

e.g for callback URL

`https://{hostname}/oauth2/callback?state=test&client_id=6b748a9e-fb67-351b-a14f-18262af05af7&scope=read&code=99726618`

When using an application to do the above call:

1. URL encode the consumer key (email) and the consumer secret (password) according to [RFC 1738](http://www.ietf.org/rfc/rfc1738.txt)
2. Concatenate the encoded consumer key, a colon character “:”, and the encoded consumer secret into a single string.
3. [Base64](https://www.ietf.org/rfc/rfc3548.txt) encode the string from the previous step.
4. The request must be a HTTP POST request.
5. The request must include an Authorization header of Basic with the encoded string from step 3.
6. The body of the request must be grant_type=authorization_code
7. The code will expire in 15 minutes.
8. Your callback URL will be requested with the 'code'
9. The callback URL is sent the code from the previous step.  Once retrieved the code will be eliminated and you must use the access token and refresh token for further requests, e.g:
`curl -k -u '34861e6d-4855-379f-89a3-ed201faa6133:c61098d9-6bef-340b-9fa7-e1fbbe7a9b4a' 'http://{hostname}/api/v1/oauth2/token?grant_type=authorization_code&code=90733383'`

```
$ curl -k -X POST -u "{client_id}:{client_secret}" \
  https://{hostname}/api/v1/oauth2/token \
  -d grant_type=authorization_code -d code={code}
```

### Token Grant

This skips a step above and provides a token directly to the return url, e.g. if using a mobile app

`https://{hostname}/oauth2/authorize?client_id={client_id}&response_type=token&scope={scope}&state={state}&redirect_uri={uri}`

This will return `token` in the callback url fragment (#) with the access token:

e.g for callback URL `https://{hostname}/oauth2/callback?state=3&client_id=34861e6d-4855-379f-89a3-ed201faa6133&scope=read%2Cwrite#token={token}`


## Refresh tokens
When access tokens expire you'll get 401 responses.

Most access token grant response therefore include a refresh token that can then be used to generate a new access token, without the need for end user participation:

### Request
```
curl -k -X POST -u "{client_id}:{client_secret}" \
	https://{hostname}/api/v1/oauth2/token \
  	-d grant_type=refresh_token -d refresh_token={refresh_token}
```

### Response

```
{
    "access_token": "{access_token}",
    "refresh_token": "{refresh_token}",
    "scope": "read write",
    "token_type": "bearer",
    "expires_in": {expires_in}
}
```

## Revoking a token
In some cases a user may wish to revoke access given to an application.  It is also possible for an application to programmatically revoke the access given to it. Programmatic revocation is important in instances where a user unsubscribes or removes an application.
In other words, part of the removal process can include an API request to ensure the permissions granted to the application are removed.

- If it is an access token, this token is revoked.
- If it is a refresh token, all access tokens issued for the refresh token are invalidated, and the refresh token is revoked.

### Request
```
curl -k -X POST -u "{client_id}:{client_secret}" https://{hostname}/api/v1/oauth2/revoke -d token={token}
```

### Response
```
{
    "revoked": {
        "refresh_token": [
            "1d600ccd-5262-3d33-9768-e61a5f519eb8"
        ],
        "access_token": [
            "8aa6aa58-60a7-37bd-b68a-ea9120d9d725"
        ]
    }
}
```

## Scopes

Scopes are defined on the client/consumer instance. When the scope parameter is provided, we will validate that it contains no scopes that were not already present on the client/consumer and fail if additional scopes are requested, but asking for fewer scopes will not affect the resulting access token.

The default scope is 'read'.
