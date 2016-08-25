<?php

use Phinx\Migration\AbstractMigration;

class Users extends AbstractMigration
{
    /**
     * Create table 'users'
     */
    public function change()
    {
        $users = $this->table('users');
        $users->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36])
              ->addColumn('password', 'string', ['comment' => 'Password', 'limit' => 16])
              ->addColumn('email', 'string', ['comment' => 'Email', 'limit' => 255])
              ->addColumn('firstname', 'string', ['comment' => 'First Name(s)', 'limit' => 128])
              ->addColumn('lastname', 'string', ['comment' => 'Last Name(s)', 'limit' => 128])
              ->addColumn('scopes', 'string', ['comment' => 'Account Scopes', 'limit' => 64, 'default' => 'user'])
              ->addColumn('status', 'string', ['comment' => 'Account Status', 'limit' => 32, 'default' => 'NEW'])
              ->addColumn('password_question', 'string', ['comment' => 'Password Hint Question', 'limit' => 255])
              ->addColumn('password_answer', 'string', ['comment' => 'Password Hint Answer', 'limit' => 255])
              ->addColumn('created', 'datetime', ['comment' => 'Created'])
              ->addColumn('login_count', 'integer', ['comment' => 'Login Count', 'default' => 0, 'signed' => 0])
              ->addColumn('login_last', 'datetime', ['comment' => 'Last Login', 'null' => true])
              ->addIndex(['uuid'], ['unique' => true])
              ->addIndex(['email'], ['unique' => true])
              ->save();
    }
}
