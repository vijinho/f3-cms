<?php

use Phinx\Migration\AbstractMigration;

class Pages extends AbstractMigration
{
    /**
     * Create OAuth2 application tokens table
     */
    public function change()
    {
        $pages = $this->table('pages');
        $pages->addColumn('uuid', 'string', ['comment' => 'UUID', 'limit' => 36, 'null' => false])
               ->addColumn('users_uuid', 'string', ['comment' => 'User UUID', 'limit' => 36, 'null' => true])
               ->addColumn('key', 'string', ['comment' => 'Key', 'limit' => 255, 'null' => true])
               ->addColumn('author', 'string', ['comment' => 'Author', 'limit' => 255, 'null' => true])
               ->addColumn('language', 'string', ['comment' => 'Language', 'limit' => 5, 'default' => 'en', 'null' => true])
               ->addColumn('status', 'string', ['comment' => 'Publish Status', 'limit' => 255, 'null' => true])
               ->addColumn('slug', 'string', ['comment' => 'Slug', 'limit' => 255, 'null' => true])
               ->addColumn('path', 'text', ['comment' => 'URL Path', 'null' => true])
               ->addColumn('keywords', 'text', ['comment' => 'Keywords', 'null' => true])
               ->addColumn('description', 'text', ['comment' => 'Description', 'null' => true])
               ->addColumn('robots', 'boolean', ['comment' => 'Allow Robots?', 'default' => 1, 'null' => true])
               ->addColumn('title', 'string', ['comment' => 'Title', 'limit' => 255, 'null' => true])
               ->addColumn('summary', 'text', ['comment' => 'Summary', 'null' => true])
               ->addColumn('body', 'text', ['comment' => 'Body Content', 'null' => true])
               ->addColumn('scopes', 'string', ['comment' => 'Scopes', 'limit' => 255, 'null' => true])
               ->addColumn('category', 'text', ['comment' => 'Categories', 'limit' => 255, 'default' => 'page', 'null' => true])
               ->addColumn('tags', 'text', ['comment' => 'Tags', 'null' => true])
               ->addColumn('metadata', 'text', ['comment' => 'Additional Metadata', 'null' => true])
               ->addColumn('created', 'datetime', ['comment' => 'Date Created'])
               ->addColumn('published', 'datetime', ['comment' => 'Date Published', 'null' => true])
               ->addColumn('expires', 'datetime', ['comment' => 'Date Expires', 'null' => true])
               ->addColumn('updated', 'datetime', ['comment' => 'Last Updated', 'null' => true])
               ->addIndex(['slug'], ['unique' => true])
               ->addIndex(['language'], ['unique' => false])
               ->addIndex(['status'], ['unique' => false])
               ->addIndex(['users_uuid'], ['unique' => false])
               ->addIndex(['uuid'], ['unique' => true])
               ->save();
    }
}
