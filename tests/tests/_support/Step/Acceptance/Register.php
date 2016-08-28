<?php
namespace Step\Acceptance;

class Register extends \AcceptanceTester
{
    public function registerAsUser()
    {
        $I = $this;
        $I->dontSeeInDatabase('users', ['email' => USER_EMAIL]);
        $I->amOnPage('/en/register');
        $I->see('Register');
        $I->fillField('#email', USER_EMAIL);
        $I->fillField('#password', USER_PASSWORD);
        $I->fillField('#confirm_password', USER_PASSWORD);
        $I->fillField('#firstname', USER_FORENAME);
        $I->fillField('#lastname', USER_SURNAME);
        $I->fillField('#password_question', USER_PW_QUESTION);
        $I->fillField('#password_answer', USER_PW_ANSWER);
        $I->click('#register');
        $I->see('Hello');
        $I->see('You successfully registered');
        $I->see('My Account');
        $I->seeInDatabase('users', ['email' => USER_EMAIL]);
    }
}
