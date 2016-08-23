<?php
$I = new ApiTester($scenario);
$I->wantTo('Get a token using client credentials grant type.');
$I->amHttpAuthenticated(CLIENT_ID, CLIENT_SECRET);

$I->sendPOST('/oauth2/token', [
    'grant_type' => 'client_credentials'
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'access_token' => 'string',
    'refresh_token' => 'string'
]);
