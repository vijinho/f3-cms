<?php

use Phinx\Migration\AbstractMigration;

class Audit extends AbstractMigration
{
    /**
     * Create hash table 'users_data'
     */
    public function change()
    {
        $users = $this->table('audit');
        $users->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36])
              ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36, 'null' => true])
              ->addColumn('ip', 'string', ['comment' => 'IP-Address', 'limit' => 16, 'null' => true])
              ->addColumn('agent', 'string', ['comment' => 'User-Agent', 'limit' => 255, 'null' => true])
              ->addColumn('created', 'datetime', ['comment' => 'Created'])
              ->addColumn('actor', 'string', ['comment' => 'Actor', 'limit' => 128, 'null' => true])
              ->addColumn('event', 'string', ['comment' => 'Event', 'limit' => 128, 'null' => true])
              ->addColumn('description', 'string', ['comment' => 'Description', 'limit' => 255, 'null' => true])
              ->addColumn('old', 'text', ['comment' => 'Old Value', 'null' => true])
              ->addColumn('new', 'text', ['comment' => 'New Value', 'null' => true])
              ->addColumn('debug', 'text', ['comment' => 'Debug Information', 'null' => true])
              ->addIndex(['ip'], ['unique' => false])
              ->addIndex(['event'], ['unique' => false])
              ->addIndex(['users_uuid'], ['unique' => false])
              ->addIndex(['uuid'], ['unique' => true])
              ->save();
    }
}
