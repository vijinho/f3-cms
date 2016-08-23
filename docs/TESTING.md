 # Test Environment

## F3 Unit Tests

 Require the file `tests/setup.php` which contains the function `setup()` which will do the following:

 - Setup the base application environment
 - Read the `dsn_test` database DSN value from the config.ini
 - Delete all existing tables in the test database
 - Execute Setup::database from `app/lib/FFCMS/Setup.php` which will import the sql dump from `data/db/sql/create.sql` and create a new root user and api app for that user.
 - It then returns an instance of $f3 which can be used by the test suite

This environment can then be used for safe testing which doesn't interfere with the running website.

### Run the test database setup and checks

```
cd tests
php setuptest.php
```

## Codeception Tests

This runs on the configured main site, not using the test database.

Codeception PHP Testing Framework is designed to work just out of the box. This means its installation requires minimal steps and no external dependencies preinstalled (except PHP, of course). Only one configuration step should be taken and you are ready to test your web application from an eye of actual user.

[Codeception Quickstart](http://codeception.com/quickstart)

### System-wide installation
```
sudo curl -LsS http://codeception.com/codecept.phar -o /usr/local/bin/codecept
sudo chmod a+x /usr/local/bin/codecept
```

### Running Tests

```
cd tests
codecept run
```

#### Acceptance Test Output

```
Codeception PHP Testing Framework v2.2.4
Powered by PHPUnit 5.4.8 by Sebastian Bergmann and contributors.

Acceptance Tests (7) ✔ AdminLoginCept: Check admin login works. (0.28s)
✔ ForgotPasswordCept: Check forgot password works. (0.66s)
✔ MyAccountCept: Check my account page works. (0.32s)
✔ RegisterAppCept: Check registering an app works. (0.44s)
✔ RegisterCept: Check registering an account works. (0.25s)
✔ UpdateMyAccountCept: Check my account updating works. (0.45s)
✔ WelcomeCept: Ensure homepage works (0.10s)
```

#### API Testing

`codecept run api`

```
Codeception PHP Testing Framework v2.2.4
Powered by PHPUnit 5.4.8 by Sebastian Bergmann and contributors.

Api Tests (7)

✔ GetUserBearerTokenCept: Get a token using a bearer token. (0.15s)
✔ GetUserCept: Use basic auth to get user information. (0.07s)
✔ GetUserClientCredentialsCept: Use basic auth to get user information. (0.09s)
✔ TokenClientCredentialsCept: Get a token using client credentials grant type. (0.08s)
✔ TokenPasswordCredentialsCept: Get a token using password credentials grant type. (0.07s)
✔ TokenRefreshCept: Get a token using client credentials grant type and refresh it. (0.14s)
✔ TokenRevokeCept: Get access/refresh tokens using client credentials grant type and revoke it. (0.14s)
```
