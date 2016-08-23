<?php

namespace FFCMS\Mappers;

use FFMVC\Helpers;
use FFCMS\Traits;

/**
 * OAuth2 Apps Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class OAuth2Apps extends Mapper
{

    protected $table = 'oauth2_apps';

    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     */
    public $fieldsVisible = [
        'created' => true,
        'users_uuid' => 'user_id',
        'client_id' => 'id',
        'client_secret' => true,
        'name' => true,
        'logo_url' => true,
        'description' => true,
        'scope' => true,
        'callback_uri' => true,
        'redirect_uris' => true,
        'status' => true,
    ];

    /**
     * Filter rules for fields
     *
     * @var array $filterRules
     * @link https://github.com/Wixel/GUMP
     */
    public $filterRules = [
        'created' => 'trim|sanitize_string',
        'users_uuid' => 'trim|sanitize_string|lower',
        'client_id' => 'trim|sanitize_string|lower',
        'client_secret' => 'trim|sanitize_string|lower',
        'name' => 'trim|sanitize_string',
        'logo_url' => 'trim|urldecode',
        'description' => 'trim|sanitize_string',
        'scope' => 'trim|sanitize_string',
        'callback_uri' => 'trim|urldecode',
        'redirect_uris' => 'trim|urldecode',
        'status' => 'trim|sanitize_string',
    ];

    /**
     * Validation rules for fields
     *
     * @var array $validationRules
     * @link https://github.com/Wixel/GUMP
     */
    public $validationRules = [
        'created' => 'date|min_len,0|max_len,19',
        'users_uuid' => 'exact_len,36|alpha_dash',
        'client_id' => 'exact_len,36|alpha_dash',
        'client_secret' => 'exact_len,36|alpha_dash',
        'name' => 'max_len,255',
        'logo_url' => 'valid_url|max_len,1024',
        'description' => 'max_len,16384',
        'scope' => 'max_len,2048',
        'callback_uri' => 'valid_url|max_len,1024',
        'redirect_uris' => 'max_len,16384',
        'status' => 'max_len,16',
    ];
}
