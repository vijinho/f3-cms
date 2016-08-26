<?php

namespace FFCMS\Controllers\API;

use FFMVC\Helpers;
use FFCMS\{Traits, Models, Mappers};


/**
 * REST API Base Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
abstract class Mapper extends API
{
    use Traits\Validation,
        Traits\SearchController;

    /**
     * For admin listing and search results
     */
    use Traits\SearchController;

    /**
     * @var object database class
     */
    protected $db;

    /**
     * @var string table in the db
     */
    protected $table = null;

    /**
     * @var string class name
     */
    protected $mapperClass;

    /**
     * @var \FFMVC\Mappers\Mapper mapper for class
     */
    protected $mapper;

    /**
     * is the user authorised for access?
     *
     * @var bool
     */
    protected $isAuthorised = false;

    /**
     * is the user authorised for access to the object type?
     *
     * @var bool
     */
    protected $adminOnly = true;


    /**
     * initialize
     * @param \Base $f3
     */
    public function __construct(\Base $f3)
    {
        parent::__construct();

        // guess the table name from the class name if not specified as a class member
        $class = \UTF::instance()->substr(strrchr(get_class($this), '\\'),1);
        $this->table = empty($this->table) ? $f3->snakecase($class) : $this->table;
        $mapperClass = "\FFCMS\Mappers\\" . $class;

        if (class_exists($mapperClass) && $this instanceof Mapper) {
            $this->mapperClass = $mapperClass;
            $this->mapper = new $this->mapperClass;
        }

        $this->isAuthorised = $this->validateAccess();
        if (empty($this->isAuthorised)) {
            $this->setOAuthError('invalid_grant');
        } elseif (empty($f3->get('isAdmin')) && !empty($this->adminOnly)) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            $this->setOAuthError('access_denied');
        } else {
            $this->isAuthorised = true;
        }
    }


    /**
     * Get the associated mapper for the table
     *
     * @return
     */
    public function &getMapper()
    {
        return $this->mapper;
    }


    /**
     * Check permissions and load the mapper with the object in the URL param @id
     * for the user
     *
     * @param \Base $f3
     * @param array $params
     * @param string $idField the field used for the unique id to load by
     * @param string|null $defaultId defaule value to use if not found
     * @return null|array|boolean|\FFMVC\Mappers\Mapper
     */
    public function getIdObjectIfUser(\Base $f3, array $params, string $idField = 'uuid', $defaultId = null)
    {
        // valid user?
        if (empty($this->isAuthorised)) {
            return;
        }

        // only admin has permission to specify @id param
        $isAdmin = $f3->get('isAdmin');
        $id = !empty($params['id']) ? $params['id'] : $f3->get('REQUEST.id');

        if ((!$isAdmin && !empty($this->adminOnly))) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }

        // use default user id
        if (empty($id)) {
            $id = $defaultId;
        }

        // load object by correct id
        $db = \Registry::get('db');
        $m = $this->getMapper();
        $m->load([$db->quotekey($idField) . ' = ?', $id]);
        if (null == $m->$idField) {
            $this->failure('authentication_error', "Object with @id does not exist.", 404);
            return $this->setOAuthError('invalid_request');
        }

        $this->mapper =& $m;
        return $m;
    }


    /**
     * Check permissions and load the mapper with the object in the URL param @id
     * if the user is an admin
     *
     * @param \Base $f3
     * @param array $params
     * @param string $idField the field used for the unique id to load by
     * @param string|null $defaultId defaule value to use if not found
     * @return null|array|boolean|\FFMVC\Mappers\Mapper
     */
    public function getIdObjectIfAdmin(\Base $f3, array $params, string $idField = 'uuid', $defaultId = null)
    {
        if (empty($this->isAuthorised)) {
            return;
        }

        // only admin has permission to delete @id
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }

        // invalid id
        $id = !empty($params['id']) ? $params['id'] : $f3->get('REQUEST.id');
        if (empty($id)) {
            $id = $defaultId;
        }

        if (!empty($id) && ('uuid' == $idField && 36 !== strlen($id))) {
            $this->failure('authentication_error', "Invalid @id parameter.", 400);
            return $this->setOAuthError('invalid_request');
        }

        // check id exists
        $db = \Registry::get('db');
        $m = $this->getMapper();
        $m->load([$db->quotekey($idField) . ' = ?', $id]);
        if (null == $m->$idField) {
            $this->failure('authentication_error', "Object with @id does not exist.", 404);
            return $this->setOAuthError('invalid_request');
        }

        $this->mapper =& $m;
        return $m;
    }


    /**
     * Display the data item
     *
     * @param \Base $f3
     * @param array $params
     * @return null|array|boolean
     */
    public function get(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('isAdmin');
        $m = $this->getIdObjectIfUser($f3, $params, 'uuid', $params['id']);
        if (!is_object($m) || null == $m->uuid) {
            return;
        } elseif (!$isAdmin && $m->users_uuid !== $f3->get('uuid')) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }
        // return raw data for object?
        $adminView = $f3->get('isAdmin') && 'admin' == $f3->get('REQUEST.view');
        $this->data = $adminView ? $m->castFields($f3->get('REQUEST.fields')) : $m->exportArray($f3->get('REQUEST.fields'));
    }


    /**
     * Replace data
     *
     * @param \Base $f3
     * @param array $params
     * @return null|array|boolean
     */
    public function put(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $params['id']);
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.uuid', $m->uuid);

        // these fields can't be modified
        return $this->save($f3, [
            'id'
        ]);
    }


    /**
     * Create new data
     *
     * @param \Base $f3
     * @return null|array|boolean
     */
    public function post(\Base $f3)
    {
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin) {
            return;
        }

        // this fields can't be modified
        $prohibitedFields = [
            'id', 'uuid'
        ];

        return $this->save($f3, $prohibitedFields);
    }


    /**
     * Update data
     *
     * @param \Base $f3
     * @param array $params
     * @return null|array|boolean
     */
    public function patch(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfAdmin($f3, $params, 'uuid', $params['id']);
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        $f3->set('REQUEST.uuid', $m->uuid);

        // these fields can't be modified
        return $this->save($f3, [
            'id', 'uuid'
        ]);
    }


    /**
     * Delete the data object indicated by @id in the request
     *
     * @param \Base $f3
     * @param array $params
     * @return null|array|boolean
     */
    public function delete(\Base $f3, array $params)
    {
        $m = $this->getIdObjectIfUser($f3, $params);
        if (!is_object($m) || null == $m->uuid) {
            return;
        }

        // a user can only delete if they own the object
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin) {
            if (!empty($m->users_uuid) && $m->users_uuid !== $f3->get('uuid')) {
                $this->failure('authentication_error', "User does not have permission.", 401);
                return $this->setOAuthError('access_denied');
            }
        }

        $this->data = [
            'deleted' => $m->erase()
        ];
    }


    /**
     * list objects (list is a reserved keyword)
     *
     * @param \Base $f3
     * @return null|array|boolean
     */
    public function listingAdmin(\Base $f3)
    {
        // must be an admin
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }

        $this->data = $this->getListingResults($f3, $this->getMapper());
    }


    /**
     * list objects (list is a reserved keyword)
     *
     * @param \Base $f3
     * @param array $params
     * @return array|boolean|null
     */
    public function listing(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin && array_key_exists('id', $params)) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        } elseif ($isAdmin && array_key_exists('id', $params)) {
            $users_uuid = $params['id'];
        } elseif (!$isAdmin) {
            $users_uuid = $f3->get('uuid');
        } else {
            $users_uuid = null;
        }

        $this->data = $this->getListingResults($f3, $this->getMapper(), $users_uuid);
    }


    /**
     * search objects
     *
     * @param \Base $f3
     * @return null|array|boolean
     */
    public function searchAdmin(\Base $f3)
    {
        // must be an admin
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        }

        $this->data = $this->getSearchResults($f3, $this->getMapper());
    }


    /**
     * search objects
     *
     * @param \Base $f3
     * @param array $params
     * @return null|array|boolean
     */
    public function search(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin && array_key_exists('id', $params)) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        } elseif ($isAdmin && array_key_exists('id', $params)) {
            $users_uuid = $params['id'];
        } elseif (!$isAdmin) {
            $users_uuid = $f3->get('uuid');
        } else {
            $users_uuid = null;
        }

        $this->data = $this->getSearchResults($f3, $this->getMapper(), $users_uuid);
    }

}
