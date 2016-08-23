<?php
$I = new ApiTester($scenario);
$I->wantTo('Get a token using password credentials grant type.');
$I->amHttpAuthenticated(CLIENT_ID, CLIENT_SECRET);

$I->sendPOST('/oauth2/token', [
    'grant_type' => 'password',
    'client_id' => CLIENT_ID,
    'username' => ADMIN_EMAIL,
    'password' => ADMIN_PASSWORD,
    'scope' => 'read,write'
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'access_token' => 'string'
]);
