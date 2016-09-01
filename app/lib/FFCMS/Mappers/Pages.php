<?php

namespace FFCMS\Mappers;

/**
 * Pages Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @property int    $id
 * @property string $uuid
 * @property string $users_uuid
 * @property string $key
 * @property string $author
 * @property string $language
 * @property string $status
 * @property string $slug
 * @property string $path
 * @property string $keywords
 * @property string $description
 * @property string $title
 * @property string $summary
 * @property string $body
 * @property string $scopes
 * @property string $categories
 * @property string $tags
 * @property string $metadata
 * @property string $created
 * @property string $published
 * @property string $updated
 */
class Pages extends Mapper
{
    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     * @link https://github.com/Wixel/GUMP
     */
    public $fieldsVisible = [
        'uuid'         => 'id',
        'users_uuid'   => 'user_id',
    ];

    /**
     * Fields that are editable to clients, boolean or string of visible field name
     *
     * @var array $fieldsEditable
     */
    protected $fieldsEditable = [
        'key',
        'author',
        'language',
        'status',
        'slug',
        'path',
        'keywords',
        'description',
        'title',
        'summary',
        'body',
        'scopes',
        'categories',
        'tags',
        'metadata',
        'published',
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
        'key'         => 'trim|sanitize_string|lower',
        'author'      => 'trim|sanitize_string',
        'language'    => 'trim|sanitize_string|lower',
        'status'      => 'trim|sanitize_string|lower',
        'slug'        => 'trim|sanitize_string|lower',
        'path'        => 'trim|sanitize_string|lower',
        'keywords'    => 'trim|sanitize_string|lower',
        'description' => 'trim|sanitize_string',
        'title'       => 'trim|sanitize_string',
        'summary'     => 'trim|sanitize_string',
        'body'        => 'trim|sanitize_string',
        'scopes'      => 'trim|sanitize_string|lower',
        'categories'  => 'trim|sanitize_string|lower',
        'tags'        => 'trim|sanitize_string|lower',
        'metadata'    => 'trim',
        'created'     => 'trim|sanitize_string',
        'published'   => 'trim|sanitize_string',
        'updated'     => 'trim|sanitize_string',
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
