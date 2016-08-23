<?php

use Phinx\Migration\AbstractMigration;

class UsersData extends AbstractMigration
{
    /**
     * Create hash table 'users_data'
     */
    public function change()
    {
        $users = $this->table('users_data');
        $users->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36])
              ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36])
              ->addColumn('key', 'string', ['comment' => 'Key', 'limit' => 255])
              ->addColumn('value', 'text', ['comment' => 'Value', 'null' => true])
              ->addColumn('type', 'string', ['comment' => 'Type', 'limit' => 255])
              ->addIndex(['uuid'], ['unique' => true])
              ->addIndex(['users_uuid', 'key'], ['unique' => true])
              ->addForeignKey('users_uuid', 'users', 'uuid', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
              ->save();
    }
}
