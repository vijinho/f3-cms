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
abstract class APIMapper extends API
{
    use Traits\Validation;

    /**
     * For admin listing and search results
     */
    use Traits\ControllerMapper;

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
     * @var \FFMVC\Models\Mapper mapper for class
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
        parent::__construct($f3);

        // guess the table name from the class name if not specified as a class member
        $class = \UTF::instance()->substr(strrchr(get_class($this), '\\'),1);
        $this->table = empty($this->table) ? $f3->snakecase($class) : $this->table;
        $mapperClass = "\FFCMS\Mappers\\" . $class;

        if (class_exists($mapperClass) && $this instanceof APIMapper) {
            $this->mapperClass = $mapperClass;
            $this->mapper = new $this->mapperClass;
        }
    }

    /**
     *
     * @param \Base $f3
     * @return void
     */
    public function init(\Base $f3)
    {
        $this->isAuthorised = $this->validateAccess();
        if (empty($this->isAuthorised)) {
            return $this->setOAuthError('invalid_grant');
        }

        $deny = false;
        $isAdmin = $f3->get('isAdmin');
        if (!$isAdmin && !empty($this->adminOnly)) {
            $deny = true;
        }

        $this->isAuthorised = empty($deny);
        if ($deny) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
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
     * @param string $defaultId defaule value to use if not found
     * @return void
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

//        if ((!$isAdmin && !empty($id)) || (!$isAdmin && !empty($this->adminOnly))) {
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
     * @param param $params
     * @param string $idField the field used for the unique id to load by
     * @param string $defaultId defaule value to use if not found
     * @return type
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
     * @return void
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
     * Delete the data object indicated by @id in the request
     *
     * @param \Base $f3
     * @param array $params
     * @return void
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
     * @return void
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
     * search objects
     *
     * @param \Base $f3
     * @return void
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
     * list objects (list is a reserved keyword)
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function listing(\Base $f3, array $params)
    {
        $isAdmin = $f3->get('isAdmin');
        $users_uuid = null;
        if (!$isAdmin && array_key_exists('id', $params)) {
            $this->failure('authentication_error', "User does not have permission.", 401);
            return $this->setOAuthError('access_denied');
        } elseif ($isAdmin && array_key_exists('id', $params)) {
            $users_uuid = $params['id'];
        } elseif (!$isAdmin) {
            $users_uuid = $f3->get('uuid');
        }

        // return raw data for object?
        $adminView = $f3->get('isAdmin') && 'admin' == $f3->get('REQUEST.view');

        // set up paging limits
        $minPerPage = $f3->get('api.paging_min');
        $maxPerPage = $f3->get('api.paging_max');
        $perPage = (int) $f3->get('REQUEST.per_page');
        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        }
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        $page = $f3->get('REQUEST.page');
        if ($page < 1) {
            $page = 1;
        }

        // fetch data (paging is 0 based)
        $m = $this->getMapper();

        // validate order field
        $order = $f3->get('REQUEST.order');
        $orderClauses = empty($order) ? [] : preg_split("/[,]/", $order);
        $allFields = $m->fields();
        foreach ($orderClauses as $k => $field) {
            // split into field, asc/desc
            $field = preg_split("/[\s]+/", trim($field));
            if (!in_array($field[0], $allFields)) {
                // invalid field
                unset($orderClauses[$k]);
                continue;
            } elseif (count($field) == 1) {
                $field[1] = 'asc';
            } elseif (count($field) == 2) {
                if (!in_array($field[1], ['asc', 'desc'])) {
                    $field[1] = 'asc';
                }
            }
            $orderClauses[$k] = $field[0] . ' ' . $field[1];
        }
        $order = join(',', $orderClauses);

        // fields to return - validate
        $fields = $f3->get('REQUEST.fields');
        $fields = empty($fields) ? [] : preg_split("/[,]/", $fields);
        foreach ($fields as $k => $field) {
            if (!in_array($field, $allFields)) {
                unset($fields[$k]);
            }
        }
        $fields = join(',', $fields);

        // count rows
        if ($isAdmin) {
            $rows = $m->count();
        } else {
            $rows = $m->count(['users_uuid = ?', $users_uuid]);
        }
        if ($rows < 1) {
            $this->failure('sever_error', "No data available for request.", 404);
            $this->setOAuthError('server_error');
            return;
        }

        // if fewer results than per page, set per_page
        if ($page == 1 && $perPage > $rows) {
            $perPage = $rows;
        }

        $pagination = [];
        $pagination['count'] = ceil($rows / $perPage);

        // too high page number?
        if ($page > $pagination['count']) {
            $page = $pagination['count'];
        }

        // set up page URLs
        $url = $f3->get('PATH');
        $urlParams = [
            'per_page' => $perPage,
        ];
        if (!empty($order)) {
            $urlParams['order'] = $order;
        }
        if (!empty($adminView)) {
            $urlParams['view'] = 'admin';
        }
        if (!empty($fields)) {
            $urlParams['fields'] = $fields;
        }
        ksort($urlParams);

        // previous page url
        $prevPage = (1 > $page - 1 ) ? null : $page - 1;
        $nextPage = (1 + $page> $pagination['count']) ? null : $page + 1;

        $resultsFrom = round($page * ($rows / $pagination['count'])) - $perPage + 1;
        $resultsTo = $resultsFrom + $perPage - 1;

        // return data
        $this->data['pagination'] = [
            'url_base' => $this->url($url, $urlParams),
            'url_current' => $this->url($url, $urlParams + ['page' => $page]),
            'url_first' => $this->url($url, $urlParams + ['page' => 1]),
            'url_last' => $this->url($url, $urlParams + ['page' => $pagination['count']]),
            'url_next' => (null == $nextPage) ? null : $this->url($url, $urlParams + ['page' => $nextPage]),
            'url_previous' => (null == $prevPage) ? null : $this->url($url, $urlParams + ['page' => $prevPage]),
            'results' => $rows,
            'results_from' => $resultsFrom,
            'results_to' => $resultsTo,
            'per_page' => $perPage,
            'pages' => $pagination['count'],
            'page' => $page,
            'object' => $m->table(),
            'fields' => preg_split("/[,]/", $fields)
        ];

        // fetch results
        if ($isAdmin && empty($users_uuid)) {
            $m->load('', [
                'order' => $order,
                'offset' => (1 == $page) ? 0 : ($page - 1) * $perPage,
                'limit' => $perPage
            ]);
        } else {
            $m->load(['users_uuid = ?', $users_uuid], [
                'order' => $order,
                'offset' => (1 == $page) ? 0 : ($page - 1) * $perPage,
                'limit' => $perPage
            ]);
        }

        do {
            $this->data['objects'][] = $adminView ? $m->castFields($fields) : $m->exportArray($fields);
        }
        while ($m->skip());
    }


    /**
     * search objects
     *
     * @param \Base $f3
     * @param array $params
     * @return void
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
        }

        // return raw data for object?
        $adminView = $f3->get('isAdmin') && 'admin' == $f3->get('REQUEST.view');

        // set up paging limits
        $minPerPage = $f3->get('api.paging_min');
        $maxPerPage = $f3->get('api.paging_max');
        $perPage = (int) $f3->get('REQUEST.per_page');
        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        }
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        $page = $f3->get('REQUEST.page');
        if ($page < 1) {
            $page = 1;
        }

        // fetch data (paging is 0 based)
        $m = $this->getMapper();
        $allFields = $m->fields();

        // validate order field
        $order = $f3->get('REQUEST.order');
        if (!empty($order)) {
            $orderClauses = empty($order) ? [] : preg_split("/[,]/", $order);
            foreach ($orderClauses as $k => $field) {
                // split into field, asc/desc
                $field = preg_split("/[\s]+/", trim($field));
                if (!in_array($field[0], $allFields)) {
                    // invalid field
                    unset($orderClauses[$k]);
                    continue;
                } elseif (count($field) == 1) {
                    $field[1] = 'asc';
                } elseif (count($field) == 2) {
                    if (!in_array($field[1], ['asc', 'desc'])) {
                        $field[1] = 'asc';
                    }
                }
                $orderClauses[$k] = $field[0] . ' ' . $field[1];
            }
            $order = join(',', $orderClauses);
        }

        // fields to return and fields to search - validate
        $validFields = [];
        foreach (['fields', 'search_fields'] as $fieldsList) {
            $fields = $f3->get('REQUEST.' . $fieldsList);
            if (empty($fields)) {
                continue;
            }
            $fields = empty($fields) ? [] : preg_split("/[,]/", $fields);
            foreach ($fields as $k => $field) {
                if (!in_array($field, $allFields)) {
                    unset($fields[$k]);
                }
            }
            $validFields[$fieldsList] = join(',', $fields);
        }

        // validated fields to return
        $fields = empty($validFields['fields']) ? join(',', $allFields) : $validFields['fields'];

        // validated fields to search in, use all if empty
        $searchFields = empty($fields) ? join(',', $allFields) : $validFields['searchFields'];

        // get search type
        $search = $f3->get('REQUEST.search');
        if (!empty($search)) {
            $search = trim(strtolower($search));
        }
        $search_type = $f3->get('REQUEST.search_type');
        if (empty($search_type)) {
            $search_type = 'exact';
        } elseif ($search_type !== 'exact') {
            $search_type = 'fuzzy';
        }

        // construct search query
        $db = \Registry::get('db');
        $sqlClauses = [];
        $searchFieldsArray = preg_split("/[,]/", $searchFields);
        foreach ($searchFieldsArray as $field) {
            $sqlClauses[] = 'LOWER(' . $db->quotekey($field) . ') = ' . $db->quote($search);
            if ($search_type == 'fuzzy') {
                $sqlClauses[] = 'LOWER(' . $db->quotekey($field) . ') LIKE ' . $db->quote('%' . $search . '%');
            }
        }

        // get total results
        $query = 'SELECT COUNT(*) AS results FROM ' . $db->quotekey($m->table()) . ' WHERE ';
        if (empty($users_uuid)) {
             $query .= join(' OR ', $sqlClauses);
        } else {
             $query .= ' users_uuid = ' . $db->quote($users_uuid)  . ' AND ('.  join(' OR ', $sqlClauses) . ')';
        }
        $rows = $db->exec($query);
        $rows = (int) $rows[0]['results'];
        if ($rows < 1) {
            $this->failure('sever_error', "No data available for request.", 404);
            $this->setOAuthError('server_error');
            return;
        }

        // if fewer results than per page, set per_page
        if ($page == 1 && $perPage > $rows) {
            $perPage = $rows;
        }

        $pagination['count'] = ceil($rows / $perPage);

        // too high page number?
        if ($page > $pagination['count']) {
            $page = $pagination['count'];
        }

        // set up page URLs
        $url = $f3->get('PATH');
        $urlParams = [
            'per_page' => $perPage,
            'search' => $search,
            'search_type' => $search_type,
        ];
        if (!empty($order)) {
            $urlParams['order'] = $order;
        }
        if (!empty($adminView)) {
            $urlParams['view'] = 'admin';
        }
        if (!empty($fields)) {
            $urlParams['fields'] = $fields;
        }
        ksort($urlParams);

        // previous page url
        $prevPage = (1 > $page - 1 ) ? null : $page - 1;
        $nextPage = (1 + $page> $pagination['count']) ? null : $page + 1;

        $resultsFrom = 1 + ($page * $perPage) - $perPage;
        $resultsTo = $resultsFrom + $perPage - 1;
        if ($resultsTo > $rows) {
            $resultsTo = $rows;
        }

        // return data
        $this->data['pagination'] = [
            'url_base' => $this->url($url, $urlParams),
            'url_current' => $this->url($url, $urlParams + ['page' => $page]),
            'url_first' => $this->url($url, $urlParams + ['page' => 1]),
            'url_last' => $this->url($url, $urlParams + ['page' => $pagination['count']]),
            'url_next' => (null == $nextPage) ? null : $this->url($url, $urlParams + ['page' => $nextPage]),
            'url_previous' => (null == $prevPage) ? null : $this->url($url, $urlParams + ['page' => $prevPage]),
            'results' => $rows,
            'results_from' => $resultsFrom,
            'results_to' => $resultsTo,
            'per_page' => $perPage,
            'pages' => $pagination['count'],
            'page' => $page,
            'object' => $m->table(),
            'fields' => preg_split("/[,]/", $fields)
        ];

        // retrieve results
        $query = 'SELECT * FROM ' . $db->quotekey($m->table()) . ' WHERE ';
        if (empty($users_uuid)) {
             $query .= join(' OR ', $sqlClauses);
        } else {
             $query .= ' users_uuid = ' . $db->quote($users_uuid)  . ' AND ('.  join(' OR ', $sqlClauses) . ')';
        }
        $query .= sprintf(' LIMIT %d,%d', (1 == $page) ? 0 : ($page - 1) * $perPage, $perPage);
        $results = $db->exec($query);
        foreach ($results as $row) {
            $this->data['objects'][] = $adminView ? $m->castFields($fields, $row) : $m->exportArray($fields, $row);
        }
    }

}
