# f3-cms - WORK-IN-PROGRESS!

Consider this a work-in-progress and an 'alpha' release.

![f3-cms homepage screenshot](tmp/screenshot.png)

Skeleton PHP 7 Fatfree-framework MVC CMS website codebase based on the simpler example project [f3-boilerplate](https://github.com/vijinho/f3-boilerplate).

**Project Goal:** Be a good and extremely flexible starting-point for implementing any kind of CMS project in F3.

## Usage

- Clone the project
- Change the `app/config/default.ini` and `config.example.ini` files to suit your project.
- Change the files in `app/lib/FFCMS` and the routes in `app/config/routes-*.ini` to suit your files.

## Is this project for you?

I wrote this project for myself, but if you are thinking to use it, thinking on the points below will help you decide.

### General

* You want to implement a website using the Fat-Free Framework
* You need a stable project that can be easily adapted and altered to suit whatever your web development needs are.
* You need to quickly and easily integrate composer classes into a project structure to get up and running ASAP.
* You need to write some boilerplate code to add project structure and initialise some commons tasks like config, logging, database connections, set up environments for production and development etc
* You may want the ability to setup your database connections in the http format - dbms://user:host@server:port/databasename
* You are thinking to run f3 on the command-line and want to see how it could be done.
* You are thinking to write an API based on REST responses and would like a starting point for to how to implement it in f3.
* You would like to see a real-life example of f3 features for render markdown, display geo-location, database connectivity.
* You want to have your project configuration split up into different files for the main configuration as have a local override file.
* You would like to have your script log how long it took to run and how much memory it used after executing when in 'development' mode.
* You need to make sure that ALL script input is normalised and cleaned by default.
* You want to use namespaces in your project

### CMS-Specific:

 * CSRF protection
 * Blacklisted IP protection (across whole site or just on login/signup pages)
 * Access to a REST JSON API via Basic Authentication or OAuth2
 * UNIX-like group-based permissions system
 * Minimal database setup tables to get up-and-running
 * Optional SQL database creation and changes using migrations
 * Data validation and filtering added to Fat-Free DB Mapper classes
 * Acceptance and Unit Tests using Codeception
 * See [doc/GUIDE.md](docs/GUIDE.md) for more

## Setup

### Composer and Webserver

- [Get Composer](https://getcomposer.org/) - `curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin -filename=composer`
- Run `composer update`
- Setup webserver config from [app/config/webserver](app/config/webserver)
- OR run with php in-built webserver from [www](www): `php -S http://127.0.0.1:8080` and browse to [http://127.0.0.1:8080](http://127.0.0.1:8080)

### Configuration
  - Edit `data/phinx.yml` with database settings for database configuration and migrations
  - Copy `app/config/config.example.ini` to `config.ini`
  - Edit `app/config/config.ini `and add anything extra from `default.ini` for overrides
  - In the top level folder `run composer install`

### Folders & Permissions

Setup empty website folders permissions on the command-line as follows:

```
mkdir -p tmp/cache tmp/sessions tmp/uploads tmp/logs
find tmp -type d -exec chmod go+rwx {} \;
```

#### Ubuntu/Debian

File ownership should be as follows:

```
sudo chown -fR www-data:www-data tmp data
```

### Setup Database

There are 3 methods to create the database, by default it is automatically created. See [docs/DATABASE.md](docs/DATABASE.md) for full details of database schema.

#### Manual method

Import the sample empty database dump file [data/db/create.mysql](data/db/create.mysql) into an empty database directly.

#### Automatic method

If the config has `db.create` set to true the file `www/index.php` or `app\lib\FFCMS\CLI.php` will attempt to list tables when booting the app and cache them for 10 minutes, if they don't exist, it will execute `app/lib/Functions/Setup::database' to:

- import to the database defined in the config using the sql file [data/db/create.mysql](data/db/create.mysql),
- cache the list of tables (10 minutes)
- create a default admin user using the config.ini setting `email.from` as the email address with 'admin' as the password
- create an entry in the oauth2_apps table to give the new admin user full REST API access

Login and change the settings for this user ASAP!

#### Migration method

For database migrations using [phinx](https://phinx.org) - [read the documentation](http://docs.phinx.org/en/latest/migrations.html) 

```
cd data
./phinx migrate
```

*Note:* On a previously created database using the manual/automatic methods above, import the SQL dump `data/db/sql/phinx.sql` and then create new migrations.

##### Seeding Test Data

Use [faker](https://github.com/fzaninotto/Faker) to seed the database.  [Phinx has integration for this](http://docs.phinx.org/en/latest/seeding.html).

```
cd app/data
php phinx seed:run
```
##### Fat-free phinx?

With phinx the following packages are installed, which might be too heavy, so it can be removed if you don't care about migrations:

  - symfony/config (v3.1.3)
  - symfony/filesystem (v3.1.3)
  - symfony/console (v3.1.3)
  - symfony/polyfill-mbstring (v1.2.0)
  - symfony/yaml (v3.1.3)
  - fzaninotto/faker (dev-master)
  - robmorgan/phinx (dev-master)

#### SSL (Optional)

I recommend using [Let's Encrypt](https://letsencrypt.org) to generate a real live valid SSL certificate for production environments.

#### Dummy certificate
`openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj "/C=GB/ST=STATE/L=TOWN/O=Office/CN=f3-cms.local" -keyout f3-cms.local.key -out f3-cms-local.crt`

Add to apache virtual host (and also see the f3-cms-ssl.local files in [app/config/webserver/](app/config/webserver/)

```
    SSLCertificateFile ssl/f3-cms.local.crt
    SSLCertificateKeyFile ssl/f3-cms.local.key
```
[MAMP](https://www.mamp.info/) lets you add the SSL file in the Hosts/SSL tab.


## Description of Project Layout

*Note:* The files that were in `app/classes/FFCMS` have now been split-out into their own repository https://github.com/vijinho/FFCMS
They can then be included in your own project by adding the same lines in your `composer.json` as used in mine here.

## Core Files/Paths
*  `lib/bcosca/fatfree-core` - [fatfree framework (core)](https://github.com/bcosca/fatfree-core) lives here
 * `www` - website and public doc root (aka `public_html` or `htdocs` etc)
 * `www/index.php` - start website application here - is the default file used by `.htaccess` for routing
 * `app` - the website application lives outside the webroot for security
 * `app/lib/FFCMS/App.php` - start fatfree project by including this file and executing Run();
 * `lib/` - all external library files/classes
 * `app/lib` - local application-specific libraries
 * `tmp/cache` `tmp/sessions` `tmp/uploads` - temporary files
 * `tmp/logs` - application logfiles
 * `data` - website data storage folder
 * `docs` - application documentation (markdown files)
 * `app/config` - application configuration files
 * `app/config/vhost` - application virtual host configuration files (apache and nginx supported)
 * `bin/cli.php` - symlink to command-line runner in `app/lib/App/CLI.php' which uses routes in `app/config/routes-cli.ini`
 * `app/en/templates/error/` - these files are standard php includes, not f3 templates, used by the error handler function
 * `app/en/templates/error/debug.phtml` - debug error page (if DEBUG=3)
 * `app/en/templates/error/404.phtml` - 'friendly' file not found page
 * `app/en/templates/error/error.phtml` - 'friendly' error page
 * `app/classes/FFCMS/App` - Base Application Classes
 * `app/classes/FFCMS/Models` - MVC Models
 * `app/classes/FFCMS/Controllers` - MVC Controllers
 * `app/classes/FFCMS/Controllers/Api` - MVC Rest API Controllers
 * `app/classes/FFCMS/Helpers` - Auxillary helper functions and utility libraries specific to the project
 * `app/classes/FFCMS/Traits` - Shared class traits
 * `app/classes/FFCMS/CLI` - Controllers for when executing in a command-line environemnt

## Supplemental Files/Paths

Used for bootstrapping the application and generic enough to be separate from the main project.

 * `lib/FFMVC/App.php` - Base Application Class to start/shutdown app
 * `lib/FFMVC/App/Helpers` - Auxillary helper functions and utility libraries specific to the project

### External Libraries

 * [DICE](https://github.com/Level-2/Dice) dependency injection container
 * [Climate](http://climate.thephpleague.com/) is used for the CLI utility methods.
 * [Wixel GUMP](https://github.com/Wixel/GUMP) for data validation
 * [Retry](https://github.com/ScriptFUSION/Retry) for retrying failed operations
 * [PHPMailer](https://github.com/PHPMailer/PHPMailer) for outgoing emails
 * [Whoops](https://github.com/filp/whoops) for nicer error handling if level of DEBUG=4

--
http://about.me/vijay.mahrra
