<?php

use Phinx\Migration\AbstractMigration;

class OAuth2Apps extends AbstractMigration
{
    /**
     * Create OAuth2 application table
     */
    public function change()
    {
        $oauthApps = $this->table('oauth2_apps');
        $oauthApps->addColumn('created', 'datetime', ['comment' => 'Created'])
              ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36])
              ->addColumn('client_id', 'string', ['comment' => 'Client Id', 'limit' => 36])
              ->addColumn('client_secret', 'string', ['comment' => 'Client Secret', 'limit' => 36])
              ->addColumn('name', 'string', ['comment' => 'Application Name', 'limit' => 255])
              ->addColumn('logo_url', 'text', ['comment' => 'Logo Image URL', 'limit' => 1024, 'null' => true])
              ->addColumn('description', 'text', ['comment' => 'Description', 'null' => true])
              ->addColumn('scope', 'text', ['comment' => 'Allowed Scopes', 'null' => true])
              ->addColumn('callback_uri', 'text', ['comment' => 'Callback URI', 'null' => true])
              ->addColumn('redirect_uris', 'text', ['comment' => 'Redirect URIs', 'null' => true])
              ->addColumn('status', 'string', ['comment' => 'Status', 'limit' => 16, 'default' => 'NEW'])
              ->addIndex(['name'], ['unique' => true])
              ->addIndex(['client_id'], ['unique' => true])
              ->addIndex(['client_secret'], ['unique' => true])
              ->addIndex(['client_id', 'client_secret'], ['unique' => true])
              ->addForeignKey('users_uuid', 'users', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->save();
    }
}
