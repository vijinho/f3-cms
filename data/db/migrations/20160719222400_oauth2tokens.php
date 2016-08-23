<?php

use Phinx\Migration\AbstractMigration;

class OAuth2Tokens extends AbstractMigration
{
    /**
    * Create OAuth2 application tokens table
      */
    public function change()
    {
        $oauthTokens = $this->table('oauth2_tokens');
        $oauthTokens->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36, 'null' => false])
              ->addColumn('created', 'datetime', ['comment' => 'Created'])
              ->addColumn('expires', 'datetime', ['comment' => 'Expires', 'null' => true])
              ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36])
              ->addColumn('client_id', 'string', ['comment' => 'Client Id', 'limit' => 36])
              ->addColumn('token', 'string', ['comment' => 'Token Value', 'limit' => 36])
              ->addColumn('type', 'string', ['comment' => 'Token Type', 'limit' => 16])
              ->addColumn('scope', 'text', ['comment' => 'Allowed Scopes', 'null' => true])
              ->addIndex(['uuid'], ['unique' => true])
              ->addIndex(['token'], ['unique' => true])
              ->addIndex(['client_id', 'users_uuid', 'type'], ['unique' => true])
              ->addForeignKey('client_id', 'oauth2_apps', 'client_id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->addForeignKey('users_uuid', 'users', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->save();
    }
}
