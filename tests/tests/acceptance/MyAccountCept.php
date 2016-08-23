<?php
use Step\Acceptance\Admin as AdminTester;

$I = new AdminTester($scenario);
$I->wantTo('Check my account page works.');
$I->loginAsAdmin();
$I->click('My Account');
$I->amOnPage('/en/user/account');
