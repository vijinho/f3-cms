<?php
use Step\Acceptance\Admin as AdminTester;

$I = new AdminTester($scenario);
$I->loginAsAdmin();
$I->wantTo('Check my account updating works.');
$I->click('My Account');
$I->amOnPage('/en/user/account');
$I->fillField('#old_password', ADMIN_PASSWORD);
$I->fillField('#firstname', 'Arnold');
$I->fillField('#lastname', 'Rimmer');
$I->fillField('#password_question', 'Your ship is?');
$I->fillField('#password_answer', 'Red Dwarf');
$I->click('#update');
$I->see('account was updated');
$I->seeInDatabase('audit', [
    'actor' => ADMIN_EMAIL,
    'event' => 'user-login'
]);
