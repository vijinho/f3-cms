<?php

namespace FFCMS\Models;

use FFMVC\Helpers;
use FFCMS\{Traits, Mappers};


/**
 * Users Model Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Users extends DB
{
    /**
     * the comma-separated list of groups a user can belong to
     *
     * @const array GROUPS
     */
    const GROUPS = ['root', 'admin', 'api', 'user'];

    /**
     * the different type of account status
     * @const array STATUSES
     */
    const STATUSES = ['registered', 'confirmed', 'suspended', 'cancelled', 'closed'];

    /**
     * @var \FFCMS\Mappers\UsersData  mapper for user
     */
    protected $dataMapper;


    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param \Log $logger
     * @param \DB\SQL $db
     */
    public function __construct(array $params = [], \Log $logger = null, \DB\SQL $db = null)
    {
        parent::__construct($params, $logger, $db);

        $this->dataMapper = new Mappers\UsersData;
    }


    /**
     * Get the associated data mapper
     *
     * @return \FFCMS\Mappers\Users
     */
    public function &getMapper()
    {
        return $this->mapper;
    }


    /**
     * Get the associated data mapper
     *
     * @return \FFCMS\Mappers\UsersData
     */
    public function &getDataMapper()
    {
        return $this->dataMapper;
    }


    /**
     * Get the user mapper by UUID
     *
     * @param string $uuid User UUID
     * @return FFCMS\Mappers\User
     */
    public function &getUserByUUID(string $uuid)
    {
        $m = $this->getMapper();
        $m->load(['uuid = ?', $uuid]);
        return $m;
    }


    /**
     * Get the user mapper by email address
     *
     * @param string $email email address
     * @return FFCMS\Mappers\User
     */
    public function &getUserByEmail(string $email)
    {
        $m = $this->getMapper();
        $m->load(['email = ?', $email]);
        return $m;
    }


    /**
     * Fetch the users data, optionally only by specified keys
     *
     * @param string $uuid
     * @param array $keys
     * @return array $data
     */
    public function getUserDetails(string $uuid, array $keys = []): array
    {
        $f3 = \Base::instance();
        $db = \Registry::get('db');

        $data = [];

        if (!empty($keys)) {

            $keys = array_map(function($key) {
                return "'$key'";
            }, $keys);

            $query = sprintf('SELECT * FROM users_data WHERE users_uuid = :uuid AND '.$db->quotekey('key').' IN (%s)',
                join(',', $keys));

        } else {
            $query = sprintf('SELECT * FROM users_data WHERE users_uuid = :uuid');
        }

        if ($rows = $db->exec($query, [':uuid' => $uuid])) {

            foreach ($rows as $r) {
                $data[$r['key']] = Helpers\Str::deserialize($r['value']);
            }

        }
        return $data;
    }


    /**
     * Perform a successful post-login action if the user is in the group 'user'
     * and is with the status 'closed', 'suspended', 'cancelled'
     *
     * @param string optional $uuid logout the current mapper user or specified one
     * @return boolean true/false if login permitted
     */
    public function login($uuid = null): bool
    {
        $usersMapper = empty($uuid) ? $this->getMapper() : $this->getUserByUUID($uuid);
        if (null == $usersMapper->uuid) {
            $msg = "User account not found for $uuid";
            throw new \FFCMS\Exception($msg);
        }

        // set user groups
        $groups = empty($usersMapper->groups) ? [] : preg_split("/[\s,]+/", $usersMapper->groups);
        if (!in_array('user', $groups) || in_array($usersMapper->status, ['closed', 'suspended', 'cancelled'])) {
            $msg = sprintf(_("User %s %s denied login because account group is not in 'user' or account status is in 'closed,suspended,cancelled'."),
                    $usersMapper->firstname, $usersMapper->lastname);
            throw new \FFCMS\Exception($msg);
        }

        $usersMapper->login_count++;
        $usersMapper->login_last = Helpers\Time::database();
        $usersMapper->validateSave();

        Audit::instance()->write([
            'users_uuid' => $usersMapper->uuid,
            'actor' => $usersMapper->email,
            'event' => 'User Login',
        ]);

        return true;
    }


    /**
     * Perform a logout action on the given user uuid
     *
     * @param string optional $uuid logout the current mapper user or specified one
     */
    public function logout($uuid = null): bool
    {
        $m = empty($uuid) ? $this->getMapper() : $this->getUserByUUID($uuid);
        if (null !== $m->uuid) {
            Audit::instance()->write([
                'users_uuid' => $m->uuid,
                'event' => 'User Logout',
                'actor' => $m->email,
            ]);
        }
        return true;
    }


    /**
     * Create a template object for a new user
     *
     * @param Mappers\Users $m User Mapper
     * @link http://fatfreeframework.com/sql-mapper
     */
    public function &newUserTemplate($m = null): \FFCMS\Mappers\Users
    {
        if (empty($m)) {
            $this->mapper->reset();
            $m = $this->mapper;
        }

        $m->uuid = $m->setUUID();
        $m->created = Helpers\Time::database();
        $m->login_count = 0;

        if (empty($m->login_last)) {
            $m->login_last = '0000-00-00 00:00:00';
        }

        if (!empty($m->password)) {
            $m->password = Helpers\Str::password($m->password);
        }

        if (empty($m->status)) {
            $m->status = 'registered';
        }

        if (empty($m->groups)) {
            $m->groups = 'user';
        }

        return $m;
    }


    /**
     * Register a new user from a newly populated usersMapper object
     *
     * @param Mappers\User $m User Mapper
     * @link http://fatfreeframework.com/sql-mapper
     */
    public function register($m = null)
    {
        if (empty($m)) {
            $m = $this->getMapper();
        }


        // try to save the data
        $m = $this->newUserTemplate($m);
        $result = $m->validateSave();
        if (true !== $result) {
            return $result;
        }

        $audit = Audit::instance();
        $audit->write([
            'users_uuid' => $m->uuid,
            'actor' => $m->email,
            'event' => 'User Registered',
            'new' => $m->cast()
        ]);

        return true;
    }


    /**
     * save (insert/update) a row to the users_data table
     * $data['value'] is automatically encoded or serialized if array/object
     *
     * @param array $data existing data to update
     * @return array $data newly saved data
     */
    public function saveKey(array $data = [])
    {
        $m = $this->getDataMapper();

        $m->load(['users_uuid = ?', $data['users_uuid']]);
        $oldData = clone $m;

        // set value based on content
        if (empty($data['value']) && !is_numeric($data['value'])) {
                // empty value should be null if not number
            $data['value'] = null;
        } else {

            $v = $data['value'];
            if (is_array($v)) {
                    // serialize to json if array
                $v = json_encode($v, JSON_PRETTY_PRINT);
            } elseif (is_object($v)) {
                    // php serialize if object
                $v = serialize($v);
            }
            $data['value'] = $v;
        }

        if (empty($m->uuid)) {
            $m->uuid = $data['users_uuid'];
        }

        $m->copyfrom($data);
        $m->validateSave();

        $audit = Audit::instance();

        $audit->write([
            'users_uuid' => $m->users_uuid,
            'event' => 'Users Data Updated',
            'old' => $oldData->cast(),
            'new' => $m->cast(),
        ]);

        return $this;
    }

}
