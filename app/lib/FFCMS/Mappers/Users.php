<?php

namespace FFCMS\Mappers;

/**
 * Users Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @property int    $id
 * @property string $uuid
 * @property string $password
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $scopes
 * @property string $status
 * @property string $password_question
 * @property string $password_answer
 * @property string $created
 * @property string $login_count
 * @property string $login_last
 */
class Users extends Mapper
{
    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     */
    public $fieldsVisible = [
        'uuid'              => 'id',
        'password'          => false,
        'email'             => true,
        'firstname'         => true,
        'lastname'          => true,
        'scopes'            => false,
        'status'            => true,
        'password_question' => true,
        'password_answer'   => true,
        'created'           => true,
        'login_count'       => false,
        'login_last'        => true,
    ];

    /**
     * Fields that are editable to clients, boolean or string of visible field name
     *
     * @var array $fieldsEditable
     */
    public $fieldsEditable = [
        'email',
        'firstname',
        'lastname',
        'password_question',
        'password_answer',
    ];

    /**
     * Filter rules for fields
     *
     * @var array $filterRules
     * @link https://github.com/Wixel/GUMP
     */
    public $filterRules = [
        'uuid'              => 'trim|sanitize_string|lower',
        'password'          => 'trim|sanitize_string',
        'email'             => 'trim|sanitize_string|sanitize_email|lower',
        'firstname'         => 'trim|sanitize_string',
        'lastname'          => 'trim|sanitize_string',
        'scopes'            => 'trim|sanitize_string|lower',
        'status'            => 'trim|sanitize_string|lower',
        'password_question' => 'trim|sanitize_string',
        'password_answer'   => 'trim|sanitize_string',
        'created'           => 'trim|sanitize_string',
        'login_count'       => 'sanitize_numbers|whole_number',
        'login_last'        => 'trim|sanitize_string',
    ];

    /**
     * Validation rules for fields
     *
     * @var array $validationRules
     * @link https://github.com/Wixel/GUMP
     */
    public $validationRules = [
        'uuid'              => 'alpha_dash',
        'email'             => 'valid_email',
        'firstname'         => 'valid_name',
    ];
}
