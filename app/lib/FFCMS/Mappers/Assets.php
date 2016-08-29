<?php

namespace FFCMS\Mappers;

/**
 * Users Assets Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @property int    $id
 * @property string $uuid
 * @property string $users_uuid
 * @property string $key
 * @property string $name
 * @property string $description
 * @property string $created
 */
class Assets extends Mapper
{
    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     * @link https://github.com/Wixel/GUMP
     */
    public $fieldsVisible = [
        'uuid'        => 'id',
        'users_uuid'  => 'user_id',
        'key'         => true,
        'name'        => true,
        'description' => true,
        'filename'    => false,
        'url'         => true,
        'type'        => true,
        'size'        => true,
        'tags'        => true,
        'metadata'    => true,
        'created'     => true,
    ];

    /**
     * Fields that are editable to clients, boolean or string of visible field name
     *
     * @var array $fieldsEditable
     */
    protected $fieldsEditable = [
        'key',
        'name',
        'description',
        'categories',
        'tags',
        'url',
        'metadata',
    ];

    /**
     * Filter rules for fields
     *
     * @var array $filterRules
     * @link https://github.com/Wixel/GUMP
     */
    public $filterRules = [
        'uuid'          => 'trim|sanitize_string|lower',
        'users_uuid'    => 'trim|sanitize_string|lower',
        'key'           => 'trim|sanitize_string|lower|slug',
        'name'          => 'trim|sanitize_string',
        'description'   => 'trim|sanitize_string',
        'filename'      => 'trim|sanitize_string|lower',
        'url'           => 'trim',
        'type'          => 'trim|sanitize_string|lower',
        'size'          => 'whole_number',
        'categories'    => 'trim|sanitize_string',
        'tags'          => 'trim|sanitize_string|lower',
        'metadata'      => 'trim',
        'created'       => 'trim|sanitize_string',
        'updated'       => 'trim|sanitize_string',
    ];

    /**
     * Validation rules for fields
     *
     * @var array $validationRules
     * @link https://github.com/Wixel/GUMP
     */
    public $validationRules = [
        'uuid'        => 'alpha_dash',
        'users_uuid'  => 'alpha_dash',
    ];
}
