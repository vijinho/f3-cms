<?php

namespace FFCMS\Mappers;

/**
 * Users Data Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @property int    $id
 * @property string $uuid
 * @property string $users_uuid
 * @property string $key
 * @property string $value
 * @property string $type
 */
class UsersData extends Mapper
{
    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     * @link https://github.com/Wixel/GUMP
     */
    public $fieldsVisible = [
        'uuid'       => 'id',
        'users_uuid' => 'user_id',
        'key'        => true,
        'value'      => true,
        'type'       => true,
    ];

    /**
     * Fields that are editable to clients, boolean or string of visible field name
     *
     * @var array $fieldsEditable
     */
    protected $fieldsEditable = [
        'value',
    ];

    /**
     * Key visibility to clients, boolean or string of visible field name
     *
     * @var array $apiFields
     * @link https://github.com/Wixel/GUMP
     */
    public $keysVisible = [
        '',
    ];

    /**
     * Filter rules for fields
     *
     * @var array $filterRules
     * @link https://github.com/Wixel/GUMP
     */
    public $filterRules = [
        'uuid'       => 'trim|sanitize_string|lower',
        'users_uuid' => 'trim|sanitize_string|lower',
        'key'        => 'trim|sanitize_string|slug',
        'value'      => 'trim',
        'type'       => 'trim|sanitize_string|lower',
    ];

    /**
     * Validation rules for fields
     *
     * @var array $validationRules
     * @link https://github.com/Wixel/GUMP
     */
    public $validationRules = [
        'uuid'       => 'max_len,36|alpha_dash',
        'users_uuid' => 'max_len,36|alpha_dash',
        'key'        => 'max_len,255',
        'value'      => 'max_len,32768',
    ];
}
