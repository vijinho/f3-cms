<?php
use Step\Acceptance\Register as RegisterTester;

$I = new RegisterTester($scenario);
$I->wantTo('Check registering an app works.');
$I->registerAsUser();
$I->click('My Apps');
$I->see('Create');
$I->fillField('#name', 'Test App');
$I->fillField('#description', 'My new amazing Test App');
$I->fillField('#callback_uri', 'http://f3-cms.local/oauth2/callback');
$I->click('#submit');
$I->see('new app has been registered');
$I->seeInDatabase('oauth2_apps', [
    'name' => 'Test App'
]);
