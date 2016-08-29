<?php

use Phinx\Migration\AbstractMigration;

class Assets extends AbstractMigration
{
    /**
     * Create OAuth2 application tokens table
     */
    public function change()
    {
        $assets = $this->table('assets');
        $assets->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36, 'null' => false])
               ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36])
               ->addColumn('key', 'string', ['comment' => 'Key', 'limit' => 255, 'null' => true])
               ->addColumn('groups', 'string', ['comment' => 'Groups', 'limit' => 255, 'null' => true])
               ->addColumn('name', 'string', ['comment' => 'Name', 'limit' => 255, 'null' => true])
               ->addColumn('description', 'text', ['comment' => 'Description', 'null' => true])
               ->addColumn('filename', 'text', ['comment' => 'Filename'])
               ->addColumn('size', 'integer', ['comment' => 'File Size'])
               ->addColumn('type', 'string', ['comment' => 'Mime Type', 'limit' => 255, 'null' => true])
               ->addColumn('categories', 'text', ['comment' => 'Categories', 'null' => true])
               ->addColumn('tags', 'text', ['comment' => 'Tags', 'null' => true])
               ->addColumn('created', 'datetime', ['comment' => 'Created'])
               ->addColumn('updated', 'datetime', ['comment' => 'Updated'])
               ->addColumn('url', 'text', ['comment' => 'URL'])
               ->addColumn('metadata', 'text', ['comment' => 'Additional Metadata'])
               ->addIndex(['key'], ['unique' => true])
               ->addIndex(['type'], ['unique' => false])
               ->addIndex(['uuid'], ['unique' => true])
               ->save();
    }
}
