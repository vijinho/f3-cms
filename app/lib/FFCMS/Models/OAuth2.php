<?php

namespace FFCMS\Models;

use FFCMS\{Traits, Mappers};

/**
 * OAuth2 Model Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class OAuth2 extends DB
{
    /**
     * the different type of account status
     * @const array STATUSES
     */
    const STATUSES = ['registered', 'confirmed', 'suspended', 'cancelled', 'closed'];

    /**
     * OAuth2 Scopes
     *
     * @const array SCOPES
     */
    const SCOPES = [
        'read' => 'Read any of your personal data',
        'write' => 'Edit all of your personal data',
    ];

    /**
     * @var \FFCMS\Mappers\Oauth2Apps  mapper for apps
     */
    protected $appsMapper;

    /**
     * @var \FFCMS\Mappers\Oauth2Tokens data mapper for apps tokens
     */
    protected $tokensMapper;


    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param null|\Log $logger
     * @param null|\DB\SQL $db
     */
    public function __construct(array $params = [], \Log $logger = null, \DB\SQL $db = null)
    {
        parent::__construct($params, $logger, $db);

        $this->appsMapper   = new Mappers\OAuth2Apps;
        $this->tokensMapper = new Mappers\OAuth2Tokens;
    }


    /**
     * Get the associated apps mapper
     *
     * @return \FFCMS\Mappers\OAuth2Apps
     */
    public function &getAppsMapper()
    {
        return $this->appsMapper;
    }


    /**
     * Get the associated app tokens mapper
     *
     * @return \FFCMS\Mappers\OAuth2Tokens
     */
    public function &getTokensMapper()
    {
        return $this->tokensMapper;
    }


    /**
     * Get users list of apps
     *
     * @param string $uuid the user uuid
     * @return \DB\SQL\Mapper[] $data new user data
     */
    public function getUserApps($uuid)
    {
        $appsMapper = $this->getAppsMapper();
        return $appsMapper->find(['users_uuid = ?', $uuid]);
    }


}
