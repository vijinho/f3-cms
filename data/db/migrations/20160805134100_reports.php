<?php

use Phinx\Migration\AbstractMigration;

class Reports extends AbstractMigration
{
    /**
     * Create OAuth2 application tokens table
     */
    public function change()
    {
        $oauthTokens = $this->table('reports');
        $oauthTokens->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36, 'null' => false])
              ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36])
              ->addColumn('scopes', 'string', ['comment' => 'Account Scopes', 'limit' => 64, 'default' => 'user'])
              ->addColumn('key', 'string', ['comment' => 'Key', 'limit' => 255])
              ->addColumn('name', 'string', ['comment' => 'Name', 'limit' => 255])
              ->addColumn('description', 'text', ['comment' => 'Description', 'null' => true])
              ->addColumn('query', 'text', ['comment' => 'Query', 'null' => true])
              ->addColumn('options', 'text', ['comment' => 'Extra Options', 'null' => true])
              ->addColumn('created', 'datetime', ['comment' => 'Created'])
              ->addIndex(['key'], ['unique' => true])
              ->addIndex(['uuid'], ['unique' => true])
              ->addForeignKey('users_uuid', 'users', 'uuid', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->save();
    }
}
