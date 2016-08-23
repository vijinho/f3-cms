<?php
$I = new ApiTester($scenario);
$I->wantTo('Use Basic Auth to get user information.');
$I->amHttpAuthenticated(ADMIN_EMAIL, ADMIN_PASSWORD);
$I->sendGET('/users');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
    'email' => ADMIN_EMAIL
]);
