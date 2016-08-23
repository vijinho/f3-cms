<?php
$I = new ApiTester($scenario);
$I->wantTo('Get access/refresh tokens using client credentials grant type and revoke it.');
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
$I->sendPOST('/oauth2/revoke', [
    'token' => $data['access_token']
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'revoked' => 'array'
]);
