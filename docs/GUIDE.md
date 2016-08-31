# Guide

# F3 Hive Variables

- **HOMEDIR** - top level folder for the project (above the www folder)
- **cfg** - extra configuration values loaded from the config_data table. cfg.keys[load,cli,www,cms,api] values are which keys to load at startup defined in the ini
- **uuid** - this is always set as the currently authanticated user (user is logged-in if set)
- **user** - this is always set as an array of the current user (user table row plus anything from users_data which might be useful to have on each request)
- **usersMapper** - mapper of the logged in user
- **userScopes** - scopes the user is a member of (user -regular user,api - api access granted,admin - cms user,root - superuser)
- **isAdmin** - whether or not the user is an admin (member of admin group) - can access cms
- **isRoot** - whether the user is a super-user - can do anything in the cms
- **apiEnabled** - whether the user has api access group memebership

## Admin

- **breadcrumbs** - array of breadcrumbs in format [text => url]

## API

When using the API, these hive variables are set:

- **api_app** - the registered api app (row from table: oauth2_apps) as an array
- **access_token** - the user's access token if set
- **user_scopes** - scopes of the user
