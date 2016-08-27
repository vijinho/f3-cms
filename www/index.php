<?php
declare (strict_types = 1);

namespace FFCMS;

/**
 * Load, configure, run application
 */
function boot()
{
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.auto_start', 'Off');
    } else {
        ini_set('session.lazy_write', 'On');
    }

    // the app folder is where the app home and files are
    chdir(__DIR__ . '/../app');
    require_once '../lib/autoload.php'; // composer autoloader

    // bootstrap initial environment
    $f3 = \Base::instance();
    \FFMVC\App::start();
    $f3->set('UNLOAD', function () {
        \FFMVC\App::finish();
    });

    // load dependency injection container
    $dice = new \Dice\Dice;

    // logging for application
    $logfile = $f3->get('log.file');
    $dice->addRule('Log', ['shared' => true, 'constructParams' => [$logfile]]);

    // database connection used by app
    $dbConfig = $f3->get('db');
    $dice->addRule('DB\\SQL', ['shared' => true, 'constructParams' => [
        \FFMVC\Helpers\DB::createDbDsn($dbConfig),
        $dbConfig['user'],
        $dbConfig['pass'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
    ]]);

    // session handler in db
    $dice->addRule('DB\\SQL\\Seession', ['shared' => true]);
    $session = $dice->create('DB\\SQL\\Session');

    // auto-create database if options set
    \FFCMS\Setup::database($dice);

    // run the main application
    require_once 'lib/FFCMS/App.php';
    $app = $dice->create('FFCMS\\App');
    $app->Main();
}

// run the application
boot();
