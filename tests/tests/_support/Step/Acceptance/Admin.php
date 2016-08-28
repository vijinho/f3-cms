<?php
namespace Step\Acceptance;

class Admin extends \AcceptanceTester
{
    public function loginAsAdmin()
    {
        $I = $this;
        $I->seeInDatabase('users', ['email' => ADMIN_EMAIL]);
        $I->amOnPage('/en/login');
        $I->fillField('#email', ADMIN_EMAIL);
        $I->fillField('#password', ADMIN_PASSWORD);
        $I->click('#login');
        $I->see('Admin');
        $I->see('Hello');
    }
}
