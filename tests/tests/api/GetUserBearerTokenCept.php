<?php
$I = new ApiTester($scenario);
$I->wantTo('Get a token using a bearer token.');
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
$I->amBearerAuthenticated($access_token);
$I->sendGET('/users');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
    'email' => 'string'
]);
