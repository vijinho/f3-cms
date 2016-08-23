<?php
$I = new ApiTester($scenario);
$I->wantTo('Get a token using client credentials grant type and refresh it.');
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
$results = $I->grabDataFromResponseByJsonPath('.');
$data = $results[0];
$access_token = $data['access_token'];
$refresh_token = $data['refresh_token'];
$I->amHttpAuthenticated(CLIENT_ID, CLIENT_SECRET);
$I->sendPOST('/oauth2/token', [
    'grant_type' => 'refresh_token',
    'refresh_token' => $refresh_token
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'access_token' => 'string'
]);
