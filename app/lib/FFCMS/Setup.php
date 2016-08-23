<?php

namespace FFCMS;

/**
 * Setup Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Setup
{

    /**
     * setup database
     *
     * @param \Dice\Dice dependency injector
     * @return void
     */
    public static function database(&$dice)
    {
        $f3 = \Base::instance();
        $cache = \Cache::instance();
        // cli mode will not use cache on cli and will check db every time if in dev mode
        if ($f3->get('db.create') && (!$cache->exists('tables', $tables) || PHP_SAPI == 'cli' || 'dev' == $f3->get('app.env'))) {
            $db = $dice->create('DB\\SQL');
            $tables = $db->exec('SHOW TABLES');
            if (empty($tables)) {
                $sql = $f3->get('HOMEDIR') . '/data/db/sql/create.sql';
                $db->exec(file_get_contents($sql));
                $tables = $db->exec('SHOW TABLES');

                // create initial admin user
                $usersModel= $dice->create('FFCMS\\Models\\Users');
                $usersMapper = $usersModel->newUserTemplate();
                $usersMapper->email = $f3->get('email.from');
                $usersMapper->firstname = 'Root';
                $usersMapper->lastname = 'Beer';
                $usersMapper->status = 'confirmed';
                $usersMapper->groups = 'user,api,admin,root';
                $usersMapper->password_question = '1+1=?';
                $usersMapper->password_answer = '2';
                $usersMapper->password = 'admin';
                $usersModel->register();

                // create initial admin api access
                $appsMapper = $dice->create('FFCMS\\Mappers\\OAuth2Apps');
                $appsMapper->name = 'Admin App';
                $appsMapper->scope = 'read,write';
                $appsMapper->status = 'approved';
                $appsMapper->created = $usersMapper->created;
                $appsMapper->users_uuid = $usersMapper->uuid;
                $appsMapper->client_id = $appsMapper->setUUID('client_id');
                $appsMapper->client_secret = $appsMapper->setUUID('client_secret');
                $appsMapper->save();
            }
            $cache->set('tables', $tables, 600);
        }
    }
}
