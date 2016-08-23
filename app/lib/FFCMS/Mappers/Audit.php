<?php

namespace FFCMS\Mappers;

/**
 * Audit Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @property int    $id
 * @property string $uuid
 * @property string $users_uuid
 * @property string $created
 * @property string $actor
 * @property string $event
 * @property string $description
 * @property string $ip
 * @property string $old
 * @property string $new
 * @property string $debug
 */
class Audit extends Mapper
{
    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     */
    public $fieldsVisible = [
        'uuid'        => true,
        'users_uuid'  => true,
        'created'     => true,
        'actor'       => true,
        'event'       => true,
        'description' => true,
        'ip'          => true,
        'old'         => true,
        'new'         => true,
        'debug'       => true,
    ];

    /**
     * Filter rules for fields
     *
     * @var array $filterRules
     * @link https://github.com/Wixel/GUMP
     */
    public $filterRules = [
        'uuid'        => 'trim|sanitize_string|lower',
        'users_uuid'  => 'trim|sanitize_string|lower',
        'created'     => 'trim|sanitize_string',
        'actor'       => 'trim|sanitize_string',
        'event'       => 'trim|sanitize_string|upper|slug',
        'description' => 'trim|sanitize_string',
        'ip'          => 'trim|sanitize_string',
        'old'         => 'trim',
        'new'         => 'trim',
        'debug'       => 'trim',
    ];

    /**
     * Validation rules for fields
     *
     * @var array $validationRules
     * @link https://github.com/Wixel/GUMP
     */
    public $validationRules = [
        'uuid'        => 'exact_len,36|alpha_dash',
        'users_uuid'  => 'exact_len,36',
        'created'     => 'date|min_len,0|max_len,19',
        'actor'       => 'max_len,128',
        'event'       => 'max_len,128',
        'description' => 'max_len,255',
        'ip'          => 'max_len,16',
        'old'         => 'max_len,32768',
        'new'         => 'max_len,32768',
        'debug'       => 'max_len,32768',
    ];
}
