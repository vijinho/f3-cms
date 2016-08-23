<?php
$I = new ApiTester($scenario);
$I->wantTo('Use Basic Auth to get user information.');
$I->amHttpAuthenticated(CLIENT_ID, CLIENT_SECRET);
$I->sendGET('/users');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
    'email' => ADMIN_EMAIL
]);
