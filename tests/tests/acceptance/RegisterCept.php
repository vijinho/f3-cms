<?php
use Step\Acceptance\Register as RegisterTester;

$I = new RegisterTester($scenario);
$I->wantTo('Check registering an account works.');
$I->registerAsUser();
