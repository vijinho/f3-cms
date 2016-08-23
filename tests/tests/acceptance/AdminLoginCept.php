<?php
use Step\Acceptance\Admin as AdminTester;

$I = new AdminTester($scenario);
$I->wantTo('Check admin login works.');
$I->loginAsAdmin();
