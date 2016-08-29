<?php

use Phinx\Migration\AbstractMigration;

class ConfigData extends AbstractMigration
{
    /**
     * Create hash table 'config_data'
     */
    public function change()
    {
        $users = $this->table('config_data');
        $users->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36])
              ->addColumn('key', 'string', ['comment' => 'Key', 'limit' => 255])
              ->addColumn('value', 'text', ['comment' => 'Value', 'null' => true])
              ->addColumn('type', 'string', ['comment' => 'Type', 'limit' => 32, 'null' => true])
              ->addColumn('options', 'text', ['comment' => 'Options', 'null' => true])
              ->addColumn('description', 'text', ['comment' => 'Description', 'null' => true])
              ->addColumn('rank', 'integer', ['comment' => 'Rank', 'default' => 9999, 'null' => true])
              ->addIndex(['rank'], ['unique' => false])
              ->addIndex(['type'], ['unique' => false])
              ->addIndex(['uuid'], ['unique' => true])
              ->addIndex(['key'], ['unique' => true])
              ->save();
    }
}
