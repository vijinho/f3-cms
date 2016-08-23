<?php
use Step\Acceptance\Admin as AdminTester;

$I = new AdminTester($scenario);
$I->wantTo('Check admin pages display.');
$I->loginAsAdmin();
$I->amOnPage('/en/admin/users/list');
$I->see('Edit');
$I->amOnPage('/en/admin/audit/list');
$I->see('View');
$I->amOnPage('/en/admin/reports/list');
$I->see('Report');
$I->amOnPage('/en/admin/config/list');
$I->see('Config');
$I->amOnPage('/en/admin/apps/list');
$I->see('Edit');
$I->amOnPage('/en/admin/apps/tokens/list');
$I->see('App Tokens');
$I->amOnPage('/en/user/apps');
$I->see('Admin App');
$I->see('Approved');
