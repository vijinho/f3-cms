<?php

namespace FFCMS\Traits;

/**
 * Controller methods which utilise a mapper
 */
trait ControllerMapper
{
   /**
     * list objects (list is a reserved keyword) of mapper
     *
     * @param \Base $f3
     * @param array $params
     * @param \FFCMS\Models\Mapper $m
     * @return array
     */
    protected function &getListingResults(\Base $f3, array $params, \FFCMS\Mappers\Mapper $m): array
    {
        // set up paging limits
        $minPerPage = 5;
        $maxPerPage = 1024;
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
        $fields = join(',', empty($fields) ? $m->fields() : $fields);

        // count rows
        $data = [];
        $rows = $m->count();
        if ($rows < 1) {
            return $data;
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
        ];
        if (!empty($order)) {
            $urlParams['order'] = $order;
        }
        if (!empty($fields)) {
            $urlParams['fields'] = $fields;
        }
        ksort($urlParams);

        // next/previous page url
        $prevPage = (1 > $page - 1 ) ? null : $page - 1;
        $nextPage = (1 + $page > $pagination['count']) ? null : $page + 1;

        $resultsFrom = round($page * ($rows / $pagination['count'])) - $perPage + 1;
        $resultsTo = $resultsFrom + $perPage - 1;

        // return data
        $data['pagination'] = [
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
            'fields' => preg_split("/[,]/", $fields),
            'view' => $f3->get('REQUEST.view')
        ];

        // fetch results
        $m->load('', [
            'order' => $order,
            'offset' => (1 == $page) ? 0 : ($page - 1) * $perPage,
            'limit' => $perPage
        ]);

        do {
            $data['objects'][] = $m->castFields($fields);
        }
        while ($m->skip());

        return $data;
    }


    /**
     * search objects of given mapper
     *
     * @param \Base $f3
     * @param array $params
     * @param \FFCMS\Models\Mapper $m
     * @return array $results
     */
    protected function &getSearchResults(\Base $f3, array $params, \FFCMS\Mappers\Mapper $m): array
    {
        // set up paging limits
        $minPerPage = 10;
        $maxPerPage = 100;
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
        $searchFields = empty($searchFields) ? join(',', $allFields) : $validFields['searchFields'];
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
        $data = [];
        $rows = $db->exec($query);
        $rows = (int) $rows[0]['results'];
        if ($rows < 1) {
            return $data;
        }

        // if fewer results than per page, set per_page
        if ($page == 1 && $perPage > $rows) {
            $perPage = $rows;
        }

        $pagination['count'] = (int) ceil($rows / $perPage);

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
        if (!empty($fields)) {
            $urlParams['fields'] = $fields;
        }
        ksort($urlParams);

        // previous page url
        $prevPage = (1 > $page - 1 ) ? null : $page - 1;
        $nextPage = (1 + $page > $pagination['count']) ? null : $page + 1;

        $resultsFrom = 1 + ($page * $perPage) - $perPage;
        $resultsTo = $resultsFrom + $perPage - 1;
        if ($resultsTo > $rows) {
            $resultsTo = $rows;
        }

        // return data
        $data['pagination'] = [
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
            'fields' => preg_split("/[,]/", $fields),
            'view' => $f3->get('REQUEST.view')
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
            $data['objects'][] = $m->castFields($fields, $row);
        }

        return $data;
    }

}
