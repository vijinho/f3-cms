<?php

namespace FFCMS\Mappers;

/**
 * Config Data Mapper Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 *
 * @property int    $id
 * @property string $uuid
 * @property string $description
 * @property string $key
 * @property string $value
 * @property string $type
 * @property string $options
 * @property string $rank
 */
class ConfigData extends Mapper
{
    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     */
    public $fieldsVisible = [
        'uuid'        => 'id',
    ];

    /**
     * Filter rules for fields
     *
     * @var array $filterRules
     * @link https://github.com/Wixel/GUMP
     */
    public $filterRules = [
        'uuid'        => 'trim|sanitize_string|lower',
        'description' => 'trim|sanitize_string',
        'key'         => 'trim|sanitize_string|lower|slug',
        'value'       => 'trim',
        'type'        => 'trim|sanitize_string|lower',
        'options'     => 'trim',
        'rank'        => 'sanitize_numbers|whole_number',
    ];

    /**
     * Validation rules for fields
     *
     * @var array $validationRules
     * @link https://github.com/Wixel/GUMP
     */
    public $validationRules = [
        'uuid'    => 'alpha_dash',
    ];
}
