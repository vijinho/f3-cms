<?php
$I = new AcceptanceTester($scenario);

$I->wantTo('Check forgot password works.');
$I->amOnPage('/en/login');
$I->click('Forgot Password?');
$I->see('Forgot Password');
$I->fillField('#email', ADMIN_EMAIL);
$I->click('#submit');
$I->see('password reset email');
$I->seeInDatabase('users_data', [
    'key' => 'forgot-password-code',
]);
$code = $I->grabFromDatabase('users_data', 'value', ['key' => 'forgot-password-code']);
$I->amOnPage('/en/forgot_password_step2');
$I->fillField('#code', $code);
$I->click('submit');
$I->see('Password code is valid');
$answer = $I->grabFromDatabase('users', 'password_answer', ['email' => ADMIN_EMAIL]);
$I->fillField('#password_answer', $answer);
$I->fillField('#password', 'password');
$I->fillField('#confirm_password', 'password');
$I->click('#submit');
$I->see('password was updated');
