#!/usr/bin/php -q
<?php
declare (strict_types=1);

namespace FFCMS;

if (PHP_SAPI !== 'cli') {
    die("This can only be executed in CLI mode.");
}

require_once 'setup.php';

// initialise test database
try {
    $f3 = setup();
    $db = \Registry::get('db');
} catch (\Exception $e) {
    // fatal, can't continue
    throw($e);
}

// load the first user
$test = new \Test;
$usersModel = new Models\Users;
$usersMapper = $usersModel->getMapper();
$usersMapper->load(['email = ?', $f3->get('email.from')]);
$test->expect(
    is_int($usersMapper->id) && $usersMapper->id == 1,
    'Default user was created successfully.'
);

// Display the results; not MVC but let's keep it simple
foreach ($test->results() as $result) {
    echo $result['text'].'<br>';
    if ($result['status']) {
        echo 'Pass';
    } else {
        echo 'Fail ('.$result['source'].')';
    }
    echo '<br>';
}
