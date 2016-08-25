# API Overview

## Endpoint

Use the following base URI to access endpoints.

`https:/{hostname}/api/v1/`

## Authentication & Authorization

The API requires the client is authenticated.

### Basic Authentication
#### via User Credentials

Authentication using `email:password` if:

- you have registered on the website (like any other user)
- your have had API access approved (users table entry has 'api' in scopes column)

##### Request

This example retrieves your users information:

`curl -k -u '{email}:{password}' -X GET https://{hostname}/api/v1/users`

Not that `-k` option should not be used on production servers as it ignores SSL certificate verification!

##### Response
```
{
    "email": "vijay@yoyo.org",
    "firstname": "Vijay",
    "lastname": "Mahrra",
    "status": "confirmed",
    "password_question": "Favourite colour?",
    "password_answer": "Pink",
    "created": 1469403081,
    "login_last": 1469809286,
    "id": "816d9200-b099-349a-9b8d-995107607a35",
    "object": "users"
}
```

#### via Client Credentials

Acquire an access token using one of the [OAuth2 grants](/docs/OAUTH2) if:

- Everything as listed in the previous section "via User Credentials"
- Have created a new 'App' in your account page - only possible after you are assigned to the 'api' group in your users table entry
- Have had your new app approved - requires your oauth2_apps entry created in the previous step has status set to 'approved'
- Note that the client_id and client_secret values (used in the example below) are auto-generated when you first register your app.

This example uses the OAuth2 client credentials flow to retrieve an access token:

##### Request
```
curl -k -X POST -u "{client_id}:{client_secret}" \
  https://{hostname}/api/v1/oauth2/token \
  -d grant_type=client_credentials
```

##### Response
```
{
    "access_token": "b893a1e4-d60a-333d-89c9-4ddf18b6a15b",
    "refresh_token": "4707647b-c6d1-3e42-b94e-5a4c89a6bd2f",
    "scope": "read write",
    "token_type": "bearer",
    "expires_in": 3600
}
```

The same credentials could have been used to retrieve user account information as in the User Credentials example above:

`curl -k -u '{email}:{password}' -X GET https://{hostname}/api/v1/users`


### OAuth2 Access Token

The API allows, and in some cases requires, requests to include an access token to authorize elevated client privileges. Pass the access token via the standard Authorization HTTP header as type Bearer.

e.g using the token from the previous example "via Client Credentials" above:

`curl -k -H "Authorization: Bearer {access_token}" https://{hostname}/api/v1/user`

Note that if the request fails due to token expiry you will need to make an OAuth2 refresh request using the refresh_token as returned in the example above.

**Tokens are passwords** Keep in mind that the client_id and client_secret, bearer token credentials, and the bearer token itself grant access to make requests on behalf of an application. These values should be considered as sensitive as passwords and must not be shared or distributed to untrusted parties.

## Request Throttling

Not implemented.

The API does not throttle client requests based on limits associated with the client's application.

## Schema

All API access is over HTTPS. All data is sent as JSON and received using standard HTTP methods.

## Custom Representations

Some fields are computationally expensive for the API to provide or require additional access privileges; and therefore, are not returned by default. Clients can specify additional fields be returned for a resource using the querystring parameter.

- Dates are returned as unixtime values
- The key row identifier is called 'id'
- Each object returned will have an 'object' identifier

### Admin users

Specifying `view=admin` for a user with admin privleges can view the full object information as returned from the database and will therefore be missing 'id', 'object' and dates in unixtime:

### Request

```
curl -k -u '{client_id:client_secret}' -X GET \
	https://{hostname}/api/v1/users/b0b913a5-31f1-3e76-b6bf-611b9984dc79?view=admin
```

### Response

```
{
    "id": 25218,
    "uuid": "b0b913a5-31f1-3e76-b6bf-611b9984dc79",
    "password": "abcb9ccbf2ab364",
    "email": "bwilkinson@gmail.com",
    "firstname": "Olaf",
    "lastname": "Lockman",
    "scopes": "api",
    "status": "confirmed",
    "password_question": "Officiis recusandae incidunt ut voluptas amet mollitia est iusto facere doloribus et dolor dolores.",
    "password_answer": "Aperiam cumque id.",
    "created": "2015-09-18 00:49:01",
    "login_count": 547,
    "login_last": "2015-11-30 11:52:56"
}
```

## Resources

The API provides a RESTful API centered around resources, identified by a URI, that can be acted upon by the standard HTTP verbs.

## HTTP

### Methods

The API strives to use appropriate HTTP verbs to perform actions on resources.

- GET - use to retrieve a resources or collection
- POST - use to create a resource or perform a custom action (create)
- PUT - use to store an entity under a specific resource (replace)
- PATCH - use to partially replace data of an entity or resource (modify)
- DELETE - use to remove a resource or entity

### Status Code Summary

- 200 - OK	Everything worked as expected.
- 400 - Bad Request	The request was unacceptable, often due to missing a required parameter.
- 401 - Unauthorized	No valid API key provided or credentials could not be validated.
- 402 - Request Failed	The parameters were valid but the request failed.
- 404 - Not Found	The requested resource doesn't exist.
- 409 - Conflict	The request conflicts with another request (perhaps due to using the same idempotent key).
- 429 - Too Many Requests	Too many requests hit the API too quickly. We recommend an exponential backoff of your requests.
- 500, 502, 503, 504 - Server Errors	Something went wrong on our end. (These are rare.)

### Redirects

The API uses HTTP redirection where appropriate. Clients should assume that any request may result in a redirection and be prepared to follow the redirect. Redirect responses will have a Location header field which contains the URI of the resource to which the client should repeat the requests. The API currently uses 302 Found and 303 See Other for redirects.

## Errors

There are the most common errors a client may receive when calling the API.

- api_connection_error	- Failure to connect to API.
- api_error	API errors - cover any other type of problem (e.g., a temporary problem - with our servers) and are extremely uncommon.
- authentication_error - Failure to properly authenticate yourself in the request.
- rate_limit_error - Too many requests hit the API too quickly.

## Parameters

Some API endpoints take parameters specified as a segment in the path.

```
curl -k -H 'Authorization: Bearer {token}' -X GET \
	https://{hostname}/api/v1/users/{id}
```

Additional options can be specified as HTTP querystring parameters.

```
curl -k -H 'Authorization: Bearer {token}' -X GET \
	https://{hostname}/api/v1/users/{id}?view=admin
```

Some resources allow filtering on their representations using the fields querystring parameter.

### Request

```
curl -k -H "Authorization: Bearer {token}" -X GET \
	https://{hostname}/api/v1/users?fields=email,firstname,lastname
```

### Response
```
{
    "email": "vijay@yoyo.org",
    "firstname": "Vijay",
    "lastname": "Mahrra",
    "id": "816d9200-b099-349a-9b8d-995107607a35",
    "object": "users"
}
```

In the last example the response will contain only the fields id and title.

## Hypermedia

All resources may have one or more URI properties linking to other resources. These provide explicit URIs to additional resources, saving API clients from the need to construct the URIs on their own.

## Pagination

Many API endpoints provide support for pagination of results. Pagination can be controlled by using querystring parameters. Default values will be used if none are provided.

An example is below for GET /api/v1/users/list

## Cross Origin Resource Sharing

We support cross origin resource sharing ([CORS](http://www.w3.org/TR/cors/)). All endpoints (except /oauth2/token and /oauth2/auth) return the following header.

`Access-Control-Allow-Origin: *`

## Localization

Not implemented.

Some endpoints may provide support for localization of certain request or response fields using the Accept-Language HTTP header. This header value defaults to en-GB if:

- the client omits the Accept-Language HTTP header,
- the client omits a locale, or
- the client specifies an unsupported locale.

e.g. to request the response in Spanish:

```
curl -k -H "Authorization: Bearer {access_token}" \
	-H "Accept-Language:es" https://{hostname}/api/v1/users`
```

## oEmbed

Not implemented.

### What is oEmbed?

oEmbed is a format for allowing an embedded representation of a URL on third party sites. The simple API allows a website to display embedded content (such as photos or videos) when a user posts a link to that resource, without having to parse the resource directly.

Full documentation and various client libraries for the oEmbed specification can be found here: http://oembed.com

### Endpoint
Use the following endpoints to access this operation:

`https://{hostname}/oembed`

### Request
The oEmbed request is a GET with the following required parameter. Note: URL encode query string parameters.

`https://{hostname}/oembed/{id}`

The oEmbed request returns JSON. If the optional "format" parameter is used it must contain the value "json". Other formats are not supported and using another value such as "xml" will result in a 501 (Not Implemented) error.


## API Endpoints

### Users

#### GET /api/v1/users

Retrieve the authenticated user details.

##### Request

Note that in the example Basic Authentication is used, but an OAuth2 token can be substituted.

```
curl -k -u '{email}:{password}' -X GET https://{hostname}/api/v1/users
```

##### Response

```
{
    "email": "bwilkinson@gmail.com",
    "firstname": "Olaf",
    "lastname": "Lockman",
    "status": "confirmed",
    "password_question": "Officiis recusandae incidunt ut voluptas amet mollitia est iusto facere doloribus et dolor dolores.",
    "password_answer": "Aperiam cumque id.",
    "created": 1442533741,
    "login_last": 1448884376,
    "id": "b0b913a5-31f1-3e76-b6bf-611b9984dc79",
    "object": "users"
}
```

#### GET /api/v1/users/@id

Retrieve a user by their id, or the authenticated user if @id parameter is omitted and the user is a member of the group 'admin'.

##### Request

Note this example uses a token.

```
curl -k -H 'Authorization: Bearer {token}' -X GET https://{hostname}/api/v1/users/b0b913a5-31f1-3e76-b6bf-611b9984dc79
```

##### Response

```
{
    "email": "bwilkinson@gmail.com",
    "firstname": "Olaf",
    "lastname": "Lockman",
    "status": "confirmed",
    "password_question": "Officiis recusandae incidunt ut voluptas amet mollitia est iusto facere doloribus et dolor dolores.",
    "password_answer": "Aperiam cumque id.",
    "created": 1442533741,
    "login_last": 1448884376,
    "id": "b0b913a5-31f1-3e76-b6bf-611b9984dc79",
    "object": "users"
}
```

#### POST /api/v1/users

Create a new user.

##### Request

Note this example uses client_id/client_secret basic authentication.

```
curl -k -X POST -u "{client_id}:{client_secret}" \
    https://{hostname}/api/v1/users \
        -d email=james.taylor@example.com \
        -d firstname=James -d lastname=Taylor \
        -d password=jtaylor123 \
        -d password_question="What is your favourite colour?" \
        -d password_answer="Red"
```

##### Response

On a successful request data will be returned of the new user object:

```
{
    "email": "james.taylor@example.com",
    "firstname": "James",
    "lastname": "Taylor",
    "status": "registered",
    "password_question": "What is your favourite colour?",
    "password_answer": "Red",
    "created": 1469973721,
    "login_last": 0,
    "id": "a1112f06-a317-3a57-9be5-83d6853d2010",
    "object": "users"
}
```

#### PATCH /api/v1/users/@id

Amend a user.

##### Request

Change the name of a user to "Jim Crow":

```
curl -k -G -X PATCH -u "{client_id}:{client_secret}" \
    https://{hostname}/api/v1/users/a1112f06-a317-3a57-9be5-83d6853d2010 \
    -d firstname=Jim -d lastname=Crow
```

##### Response

On a successful request data will be returned of the amended user object:

```
{
    "email": "james.taylor@example.com",
    "firstname": "Jim",
    "lastname": "Crow",
    "status": "registered",
    "password_question": "What is your favourite colour?",
    "password_answer": "Red",
    "created": 1469973721,
    "login_last": 0,
    "id": "a1112f06-a317-3a57-9be5-83d6853d2010",
    "object": "users"
}
```

#### PUT /api/v1/users/@id

Replace all details of a user (except for the ID/UUID)  This requires you send all required data - anything missing will fail.

##### Request

Note the use of `-G` to send the data with `-d` as GET because PATCH does not support -d as POST.
id/uuid and date created fields are not changed.


```
curl -k -G -X PATCH -u "{client_id}:{client_secret}" \
    https://{hostname}/api/v1/users/a1112f06-a317-3a57-9be5-83d6853d2010 \
        -d firstname=Roberto \
        -d lastname=Baggio \
        -d email=roberto.baggio@example.com \
        -d status=registered -d password=arse123 \
        -d scopes='admin,api' \
        -d password_question='Italian?' \
        -d password_answer="Si" \
        -d login_last="2014-12-01"
```

##### Response

On a successful request data will be returned of the updated user object:

```
{
    "email": "roberto.baggio@example.com",
    "firstname": "Roberto",
    "lastname": "Baggio",
    "status": "registered",
    "password_question": "Italian?",
    "password_answer": "Si",
    "created": 1469973721,
    "login_last": 0,
    "id": "a1112f06-a317-3a57-9be5-83d6853d2010",
    "object": "users"
}
```

#### GET /api/v1/users/list

List users. Valid request parameters are:

- page (default: 1)
- per_page - quantity of results per page (defaults defined in config file)
- order - fields to order by, comma-separated, e.g. 'lastname desc,email asc'

##### Request

List users in the following order:

- order results by email (ascending)
- show 25 results per page
- retrieve page 3 of results
- return email,lastname, firstname per result object

```
curl -k -G -X GET -u "{client_id}:{client_secret}" \
    https://{hostname}/api/v1/users/list \
        -d per_page=25 -d page=3 -d fields=email,lastname,firstname -d order=email
```

Invalid fields in the 'order' clause will be removed, 'asc' will be added by default to fields missing 'asc/desc'

##### Response

The pagination section has the URLs for paging the results, whilst objects are the actual listing results of users.

Note url_next and url_previous will be null if first or last page.

```
{
    "pagination": {
        "url_first": "https:\/\/{hostname}\/api\/users\/list?fields=email%2Clastname%2Cfirstname&order=email+asc&pages=101&per_page=25&page=1",
        "url_last": "https:\/\/{hostname}\/api\/users\/list?fields=email%2Clastname%2Cfirstname&order=email+asc&pages=101&per_page=25&page=101",
        "url_next": "https:\/\/{hostname}\/api\/users\/list?fields=email%2Clastname%2Cfirstname&order=email+asc&pages=101&per_page=25&page=101",
        "url_previous": "https:\/\/{hostname}\/api\/users\/list?fields=email%2Clastname%2Cfirstname&order=email+asc&pages=101&per_page=25&page=2",
        "results": "2501",
        "per_page": 25,
        "pages": 101,
        "page": "3"
    },
    "objects": [
        {
            "email": "alice65@gmail.com",
            "firstname": "Burnice",
            "lastname": "Harris",
            "id": "b3e387ea-36e7-3cf5-9b61-63d3df22cef4",
            "object": "users"
        },
        {
            "email": "alisha11@ankunding.com",
            "firstname": "Derick",
            "lastname": "O'Connell",
            "id": "54de2027-f174-3f61-8a1a-b2dd955277e6",
            "object": "users"
        },
        ... 23 more...
    }
}
```

#### GET /api/v1/users/search

Search users. Valid request parameters are as for /api/v1/users/list and:

- search - comma-separated list of words to search for
- search_type - exact|fuzzy - keyword matching in fields should be exact or fuzzy
- search_fields - fields to search in (defaults to all otherwise)

##### Request

Search for 'vija' in users, fuzzy search, searching 'firstname' and 'lastname'.

```
curl -k -G -X GET -u "{client_id}:{client_secret}" \
    -d https://{hostname}/api/v1/users/search -\
    -d search=vija
    -d search_type=fuzzy
    -d search_fields=firstname,lastname
```

#### Response

```
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/users\/search?pages=1&per_page=1&page=1",
        "url_first": "https:\/\/{hostname}\/api\/users\/search?pages=1&per_page=1&page=1",
        "url_last": "https:\/\/{hostname}\/api\/users\/search?pages=1&per_page=1&page=1",
        "url_next": null,
        "url_previous": null,
        "results": 1,
        "per_page": 1,
        "pages": 1,
        "page": "1"
    },
    "objects": [
        {
            "email": "vijay@yoyo.org",
            "firstname": "Vijay",
            "lastname": "Mahrra",
            "status": "confirmed",
            "password_question": "Favourite colour?",
            "password_answer": "Pink",
            "created": 1469298592,
            "login_last": 1469974688,
            "id": "55f930b4-513f-3d22-84ed-c8b30ebaeaed",
            "object": "users"
        }
    ]
}
```

#### GET /api/v1/users_data/@id

Retrieve an users data entry by id.

##### Request

Retrieve users_data record f480d772-ee6d-3c7b-b174-3784a13e7aaa

```
curl -k -G -X GET -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/users_data/f480d772-ee6d-3c7b-b174-3784a13e7aaa
```

##### Response

```
{
    "key": "email_confirmed",
    "value": "1",
    "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
    "user_id": "ffbf0dbc-a418-3ae9-8449-2c526a3b7179",
    "object": "usersdata"
}```

#### POST /api/v1/users_data

Create a new key,value entry for a user.

A regular user obviously cannot specify the users_uuid as they can only access their own data.

##### Request

```
curl -k -X POST -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/users_data \
    -d users_uuid=eca6783e-3ec6-33dc-878d-d8fb2a322d02 \
    -d key=test \
    -d value=123
```

##### Response

```
{
    "key": "test",
    "value": "123",
    "id": "d8b14cb1-d4a3-3a88-8b72-241ef3cff122",
    "user_id": "eca6783e-3ec6-33dc-878d-d8fb2a322d02",
    "object": "usersdata"
}
```

#### PUT /api/v1/users_data/@id

Replace the existing key,value for an object

##### Request

```
curl -k -G -X PUT -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/users_data/d8b14cb1-d4a3-3a88-8b72-241ef3cff122 \
    -d key=test12
    -d value=12345a
```

##### Response

```
{
    "key": "test12",
    "value": "12345a",
    "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
    "user_id": "ffbf0dbc-a418-3ae9-8449-2c526a3b7179",
    "object": "usersdata"
}
```

#### PATCH /api/v1/users_data/@id

Update the value for a key with id d8b14cb1-d4a3-3a88-8b72-241ef3cff122 to 1234

##### Request

```
curl -k -G -X PATCH -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/users_data/d8b14cb1-d4a3-3a88-8b72-241ef3cff122 \
    -d value=1234
```

##### Response

```
{
    "key": "test",
    "value": "1234",
    "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
    "user_id": "ffbf0dbc-a418-3ae9-8449-2c526a3b7179",
    "object": "usersdata"
}
```

#### DELETE /api/v1/users_data/@id

Delete an entry

##### Request

```
curl -k -G -X PATCH -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/users_data/b9fb6e7f-7edc-3959-910b-b34ddce04084
```

##### Response

```
{
    "deleted": 1
}
```

#### GET /api/v1/users_data/list

List users data entries for authenticated user. If admin will list all entries for all users.

##### Request

```
curl -k -G -X GET -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/users_data/list
```

##### Response

```
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/users_data\/list?pages=1&per_page=1&page=1",
        "url_first": "https:\/\/{hostname}\/api\/users_data\/list?pages=1&per_page=1&page=1",
        "url_last": "https:\/\/{hostname}\/api\/users_data\/list?pages=1&per_page=1&page=1",
        "url_next": null,
        "url_previous": null,
        "results": "1",
        "per_page": "1",
        "pages": 1,
        "page": 1
    },
    "objects": [
        {
            "key": "email_confirmed",
            "value": "1",
            "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
            "user_id": "ffbf0dbc-a418-3ae9-8449-2c526a3b7179",
            "object": "usersdata"
        }
    ]
}
```

#### GET /api/v1/users_data/list/@id

List users data entries for user @id.

##### Request

```
curl -k -G -X GET -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/users_data/list/ffbf0dbc-a418-3ae9-8449-2c526a3b7179
```

##### Response

```
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/users_data\/list?pages=1&per_page=1&page=1",
        "url_first": "https:\/\/{hostname}\/api\/users_data\/list?pages=1&per_page=1&page=1",
        "url_last": "https:\/\/{hostname}\/api\/users_data\/list?pages=1&per_page=1&page=1",
        "url_next": null,
        "url_previous": null,
        "results": "1",
        "per_page": "1",
        "pages": 1,
        "page": 1
    },
    "objects": [
        {
            "key": "email_confirmed",
            "value": "1",
            "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
            "user_id": "ffbf0dbc-a418-3ae9-8449-2c526a3b7179",
            "object": "usersdata"
        }
    ]
}
```

#### GET /api/v1/users_data/search

Search current user if not admin, or all users if admin user.

##### Request

e.g. search for email

```
curl -k -G -X GET -H 'Authorization: Bearer 1ceaedc8-420f-3f34-afb4-ab8d0e26c4cc' \
    https://{hostname}/api/v1/users_data/search \
    -d search=email \
    -d search_type=fuzzy
```

##### Response

```
curl -k -G -X GET -H 'Authorization: Bearer 1ceaedc8-420f-3f34-afb4-ab8d0e26c4cc' https://{hostname}/api/v1/users_data/search -d search=email -d search_type=fuzzy
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/users_data\/search?fields=id%2Cuuid%2Cusers_uuid%2Ckey%2Cvalue&pages=1&per_page=1&page=1",
        "url_first": "https:\/\/{hostname}\/api\/users_data\/search?fields=id%2Cuuid%2Cusers_uuid%2Ckey%2Cvalue&pages=1&per_page=1&page=1",
        "url_last": "https:\/\/{hostname}\/api\/users_data\/search?fields=id%2Cuuid%2Cusers_uuid%2Ckey%2Cvalue&pages=1&per_page=1&page=1",
        "url_next": null,
        "url_previous": null,
        "results": 1,
        "per_page": 1,
        "pages": 1,
        "page": 1
    },
    "objects": [
        {
            "key": "email_confirmed",
            "value": "1",
            "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
            "object": "usersdata"
        }
    ]
}
```

#### GET /api/v1/users_data/search/@id

Search users specified by @id.

##### Request

e.g. search for email of user ffbf0dbc-a418-3ae9-8449-2c526a3b7179

```
curl -k -G -X GET -u '{client_id}:{client_secret}' \
    -d https://{hostname}/api/v1/users_data/search/ffbf0dbc-a418-3ae9-8449-2c526a3b7179
    -d search=email
    -d search_type=fuzzy
```

##### Response

```
curl -k -G -X GET -H 'Authorization: Bearer 1ceaedc8-420f-3f34-afb4-ab8d0e26c4cc' https://{hostname}/api/v1/users_data/search -d search=email -d search_type=fuzzy
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/users_data\/search?fields=id%2Cuuid%2Cusers_uuid%2Ckey%2Cvalue&pages=1&per_page=1&page=1",
        "url_first": "https:\/\/{hostname}\/api\/users_data\/search?fields=id%2Cuuid%2Cusers_uuid%2Ckey%2Cvalue&pages=1&per_page=1&page=1",
        "url_last": "https:\/\/{hostname}\/api\/users_data\/search?fields=id%2Cuuid%2Cusers_uuid%2Ckey%2Cvalue&pages=1&per_page=1&page=1",
        "url_next": null,
        "url_previous": null,
        "results": 1,
        "per_page": 1,
        "pages": 1,
        "page": 1
    },
    "objects": [
        {
            "key": "email_confirmed",
            "value": "1",
            "id": "f480d772-ee6d-3c7b-b174-3784a13e7aaa",
            "object": "usersdata"
        }
    ]
}
```

### Audit

#### POST /api/v1/audit

Create an audit record

##### Request

```
curl -k -X POST -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/audit \
    -d event=test \
    -d description=test \
```

##### Response

```
{
    "uuid": "cfa909dd-d1dd-3746-8efd-2ab4bf51d8a3",
    "users_uuid": "04c2b59f-ec9f-33b0-8915-4b2f9bbcadd1",
    "created": 1470180026,
    "actor": "vijay@yoyo.org",
    "event": "test",
    "description": "test",
    "old": "",
    "new": "",
    "debug": "",
    "object": "audit"
}
```

#### GET /api/v1/audit/@id

Retrieve an audit record by id.

##### Request

Retrieve audit record 26bb5c63-4f16-3c35-bdcf-714af5381f92

```
 curl -k -G -X GET -u "{client_id}:{client_secret}"
    https://{hostname}/api/v1/audit/26bb5c63-4f16-3c35-bdcf-714af5381f92 \
    -d view=admin
```

##### Response

```
{
    "id": 1,
    "uuid": "26bb5c63-4f16-3c35-bdcf-714af5381f92",
    "users_uuid": "da00f425-7c57-38df-b598-8040904ea98a",
    "created": "2016-07-23 19:29:52",
    "actor": "vijay@yoyo.org",
    "event": "USER_REGISTERED",
    "description": "New user Vijay Mahrra registration.",
    "old": null,
    "new": "{\n    \"id\": 1,\n    \"uuid\": \"da00f425-7c57-38df-b598-8040904ea98a\",\n    \"password\": \"sbo95g5lhzac\",\n    \"email\": \"vijay@yoyo.org\",\n    \"firstname\": \"Vijay\",\n    \"lastname\": \"Mahrra\",\n    \"scopes\": \"user\",\n    \"status\": \"registered\",\n    \"password_question\": \"Favourite colour?\",\n    \"password_answer\": \"Pink\",\n    \"created\": \"2016-07-23 19:29:52\",\n    \"login_count\": 0,\n    \"login_last\": null\n}",
    "debug": null
}
```

#### GET /api/v1/audit/list

##### Request

```
curl -k -G -X GET -u "{client_id}:{client_secret}" \
    https://{hostname}/api/v1/audit/list \
    -d per_page=10 \
    -d fields=actor,event,description \
    -d order=actor
```

##### Response

```
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/audit\/list?fields=actor%2Cevent%2Cdescription&order=actor+asc&pages=2&per_
page=10&page=1",
        "url_first": "https:\/\/{hostname}\/api\/audit\/list?fields=actor%2Cevent%2Cdescription&order=actor+asc&pages=2&per_pa
ge=10&page=1",
        "url_last": "https:\/\/{hostname}\/api\/audit\/list?fields=actor%2Cevent%2Cdescription&order=actor+asc&pages=2&per_pag
e=10&page=2",
        "url_next": "https:\/\/{hostname}\/api\/audit\/list?fields=actor%2Cevent%2Cdescription&order=actor+asc&pages=2&per_pag
e=10&page=2",
        "url_previous": null,
        "results": "17",
        "per_page": 10,
        "pages": 2,
        "page": 1
    },
    "objects": [
        {
            "actor": "",
            "event": "USER_DATA_UPDATED",
            "description": "User updated value for key &#39;email_confirmed&#39;",
            "object": "audit"
        },
        {
            "actor": "",
            "event": "USER_DATA_UPDATED",
            "description": "User updated value for key &#39;email_confirmed&#39;",
            "object": "audit"
        },
        ...
```

#### GET /api/v1/audit/search

##### Request

```
curl -k -G -X GET -u "{client_id}:{client_secret}" \
    https://{hostname}/api/v1/audit/list \
    -d fields=actor,event,description,created \
    -d order=created
    -d search=USER_LOGIN
```

##### Response

```
{
    "pagination": {
        "url_current": "https:\/\/{hostname}\/api\/audit\/search?fields=actor%2Cevent%2Cdescription%2Ccreated&order=created+asc&pages=1&per_page=7&page=1",
        "url_first": "https:\/\/{hostname}\/api\/audit\/search?fields=actor%2Cevent%2Cdescription%2Ccreated&order=created+asc&pages=1&per_page=7&page=1",
        "url_last": "https:\/\/{hostname}\/api\/audit\/search?fields=actor%2Cevent%2Cdescription%2Ccreated&order=created+asc&pages=1&per_page=7&page=1",
        "url_next": null,
        "url_previous": null,
        "results": 7,
        "per_page": 7,
        "pages": 1,
        "page": 1
    },
    "objects": [
        {
            "created": 1469968626,
            "actor": "vijay@yoyo.org",
            "event": "USER_LOGIN",
            "description": "User Vijay Mahrra logged in.",
            "object": "audit"
        },
        {
            "created": 1469968649,
            "actor": "vijay@yoyo.org",
            "event": "USER_LOGIN",
            "description": "User Vijay Mahrra logged in.",
            "object": "audit"
        },
        ...
```

### Config

#### GET /api/v1/config_data/@id

Retrieve an config data record by id.

##### Request

```
 curl -k -G -X GET -u "{client_id}:{client_secret}"
    https://{hostname}/api/v1/config_data/{id} \
    -d view=admin
```

##### Response

#### POST /api/v1/config_data

Add a new config_data key,value setting

##### Request

```
curl -k -X POST -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/config_data \
    -d key=twitter \
    -d type=url \
    -d value=https://twitter.com/vijinh0
    -d rank=3
```

##### Response

```
{
    "uuid": "596e13ce-d1f5-3fba-9403-e218506ee6f3",
    "key": "twitter",
    "value": "https:\/\/twitter.com\/vijinh0",
    "type": "url",
    "options": "",
    "rank": 3,
    "object": "configdata"
}
```

#### PUT /api/v1/config_data/@id

Replace an existing key, value setting

##### Request

```
curl -k -G -X PUT -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/config_data/596e13ce-d1f5-3fba-9403-e218506ee6f3 \
    -d key=facebook \
    -d type=url \
    -d value=https://facebook.com/mahrra
    -d rank=1
```

##### Response

```
{
    "uuid": "596e13ce-d1f5-3fba-9403-e218506ee6f3",
    "key": "facebook",
    "value": "https:\/\/facebook.com\/mahrra",
    "type": "url",
    "options": "",
    "rank": 1,
    "object": "configdata"
}
```

#### PATCH /api/v1/config_data/@id

Replace an existing key, value setting

##### Request

```
curl -k -G -X PUT -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/config_data/596e13ce-d1f5-3fba-9403-e218506ee6f3 \
    -d rank=99
```

##### Response

```
{
    "uuid": "596e13ce-d1f5-3fba-9403-e218506ee6f3",
    "key": "facebook",
    "value": "https:\/\/facebook.com\/mahrra",
    "type": "url",
    "options": "",
    "rank": 99,
    "object": "configdata"
}
```

#### DELETE /api/v1/config_data/@id

DELETE an existing key, value setting

##### Request

```
curl -k -X DELETE -u '{client_id}:{client_secret}' \
    https://{hostname}/api/v1/config_data/596e13ce-d1f5-3fba-9403-e218506ee6f3 \
```

##### Response

```
{
    "deleted": 1
}
```

#### GET /api/v1/config_data/list

List config data.

##### Request

```
curl -k -G -X GET -u "{client_id}:{client_secret}" https://{hostname}/api/v1/config_data/list
```

##### Response

```
{
}
```

#### GET /api/v1/config_data/search

Search config data

##### Request

```
curl -k -G -X GET -u "{client_id}:{client_secret}" https://{hostname}/api/v1/config_data/search
```

##### Response

```
{
}
```


### OAuth2

#### GET /api/v1/oauth2_apps/@id

Retrieve an oauth2 app record by id.

##### Request

```
 curl -k -G -X GET -u "{client_id}:{client_secret}"
    https://{hostname}/api/v1/oauth2_apps/{id} \
    -d view=admin
```

##### Response

```
{
    "created": 1469472222,
    "client_secret": "a65ac83c-937a-318a-b6de-39ed235317cb",
    "name": "Fight The Power!",
    "logo_url": "",
    "description": "This is the answer to all of the world&#39;s problems.",
    "scope": "",
    "callback_uri": "https:\/\/{hostname}\/oauth2\/callback",
    "redirect_uris": "",
    "status": "approved",
    "user_id": "55f930b4-513f-3d22-84ed-c8b30ebaeaed",
    "id": "6b748a9e-fb67-351b-a14f-18262af05af7",
    "object": "oauth2apps"
}
```

#### GET /api/v1/oauth2_apps/list

##### Request

##### Response

#### GET /api/v1/oauth2_apps/search

##### Request

##### Response

#### POST /api/v1/oauth2_apps

Create a new OAuth2 app

##### Request

```
curl -k -X POST -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/oauth2_apps \
    -d name=test123
```

##### Response

```
{
    "created": 1470180720,
    "client_id": "96c98c84-9659-3bc5-ae74-d187a6384549",
    "client_secret": "862bd53b-59b4-3dd4-98e1-94c93fda972f",
    "name": "test123",
    "logo_url": "",
    "description": "",
    "scope": "",
    "callback_uri": "",
    "redirect_uris": "",
    "status": "approved",
    "user_id": "04c2b59f-ec9f-33b0-8915-4b2f9bbcadd1",
    "id": "96c98c84-9659-3bc5-ae74-d187a6384549",
    "object": "oauth2apps"
}
```

#### PATCH /api/v1/oauth2_apps/@id

Amend a OAuth2 app

##### Request

Change user of app:

```
curl -G -k -X PATCH -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/oauth2_apps/96c98c84-9659-3bc5-ae74-d187a6384549 \
    -d user_uuid=1725d332-b3a7-38a3-9c00-91513bbd74ab
```

##### Response

```
{
    "created": 1470180720,
    "client_secret": "862bd53b-59b4-3dd4-98e1-94c93fda972f",
    "name": "test123",
    "logo_url": "",
    "description": "",
    "scope": "",
    "callback_uri": "",
    "redirect_uris": "",
    "status": "approved",
    "user_id": "1725d332-b3a7-38a3-9c00-91513bbd74ab",
    "id": "96c98c84-9659-3bc5-ae74-d187a6384549",
    "object": "oauth2apps"
}
```

#### GET /api/v1/oauth2_tokens/@id

Retrieve an oauth2 token record by id.

##### Request

```
 curl -k -G -X GET -u "{client_id}:{client_secret}"
    https://{hostname}/api/v1/oauth2_tokens/{id} \
    -d view=admin
```

##### Response

#### GET /api/v1/oauth2_tokens/list

##### Request

##### Response

#### GET /api/v1/oauth2_tokens/search

##### Request

##### Response

#### POST /api/v1/oauth2_tokens

Create a new token.

##### Request

Create a token for a client_id and current user

```
curl -k -X POST -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/oauth2_tokens \
    -d expires='2017-12-01 23:20:00' \
    -d client_id=96c98c84-9659-3bc5-ae74-d187a6384549
```

##### Response

```
{
    "created": 1470182618,
    "expires": 1512170400,
    "client_id": "96c98c84-9659-3bc5-ae74-d187a6384549",
    "token": "57644326-19dd-378e-a818-3ac78776689f",
    "type": "access_token",
    "scope": "read,write",
    "id": "31fec9b7-5cce-3362-87eb-8a0a0a0b1870",
    "user_id": "04c2b59f-ec9f-33b0-8915-4b2f9bbcadd1",
    "object": "oauth2tokens"
}
```

#### PATCH /api/v1/oauth2_tokens/@id

Amend a token.

##### Request

Change token date:

```
curl -G -k -X PATCH -H 'Authorization: Bearer {token}' \
    https://{hostname}/api/v1/oauth2_tokens/31fec9b7-5cce-3362-87eb-8a0a0a0b1870 \
    -d expires='2021-12-01 23:20:00'
```

##### Response

```
{
    "created": 1470182618,
    "expires": "2021-12-01",
    "client_id": "96c98c84-9659-3bc5-ae74-d187a6384549",
    "token": "6ba2d3ab-d547-3a4b-ad73-72b3066dd5ef",
    "type": "access_token",
    "scope": "read",
    "id": "31fec9b7-5cce-3362-87eb-8a0a0a0b1870",
    "user_id": "04c2b59f-ec9f-33b0-8915-4b2f9bbcadd1",
    "object": "oauth2tokens"
}
````
