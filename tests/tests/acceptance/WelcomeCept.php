<?php
  $I = new AcceptanceTester($scenario);
  $I->wantTo('Ensure homepage works');
  $I->amOnPage('/en');
  $I->see('Home');
