<?php

namespace FFCMS\Models;

use FFCMS\{Traits, Mappers, Exceptions};

/**
 * Assets Model Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Assets extends DB
{

    /**
     * @var \FFCMS\Mappers\Assets  mapper for asset
     */
    public $mapper;

    /**
     * initialize with array of params, 'db' and 'logger' can be injected
     *
     * @param null|\Log $logger
     * @param null|\DB\SQL $db
     */
    public function __construct(array $params = [], \Log $logger = null, \DB\SQL $db = null)
    {
        parent::__construct($params, $logger, $db);

        $this->mapper = new Mappers\Assets;
    }

    /**
     * Get the associated asset mapper
     *
     * @return \FFCMS\Mappers\Assets
     */
    public function &getMapper()
    {
        return $this->mapper;
    }

    /**
     * Return the URL path to the asset
     *
     * @param string $assetPath the path in assets, must prefix slash, no trailing slash
     * @return string return the url path or false if not exists
     */
    public function assetUrlPath($assetPath): string
    {
        $f3 = \Base::instance();
        return $f3->get('assets.url') . $assetPath;
    }

    /**
     * Create if needed, and return the dir to the asset path
     *
     * @return string string $assetPath the path in assets
     */
    public function assetDirPath($assetPath): string
    {
        $f3  = \Base::instance();
        $dir = $f3->get('assets.dir') . $assetPath;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir . '/';
    }

    /**
     * Create if needed, and return the path to the asset file path
     *
     * @param string $dirPath dir for the $filename
     * @param string $filename filename for asset
     * @return string $path to the asset
     */
    public function assetFilePath($dirPath, $filename): string
    {
        return $this->assetDirPath($dirPath) . $filename;
    }

    /**
     * Return the URL path to the asset if exists or false
     *
     * @param string $dirPath dir for the $filename
     * @return string $path to the asset
     * @return bool true if the asset exists
     */
    public function assetExists($dirPath, $filename)
    {
        return file_exists($this->assetFilePath($dirPath, $filename));
    }

    /**
     * Return the URL path to the asset if exists or false
     *
     * @param string $dirPath dir for the $filename
     * @param string $uuid the user uuid
     * @return false|string return the url path or false if not exists
     */
    public function assetUrl($dirPath, $filename)
    {
        $url = $this->assetExists($dirPath, $filename) ? $this->assetUrlPath($dirPath . '/' . $filename) : false;
        if (empty($url)) {
            return false;
        }
        return $url . '?' . filesize($this->assetFilePath($dirPath, $filename));
    }
}
