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
 * @property string $category
 * @property string $tags
 * @property string $metadata
 * @property string $created
 * @property string $published
 * @property string $updated
 * @property boolean $robots
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
        'category',
        'tags',
        'metadata',
        'published',
        'robots',
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
        'key'         => 'trim|sanitize_string|lower|slug',
        'author'      => 'trim|sanitize_string',
        'language'    => 'trim|sanitize_string|lower',
        'status'      => 'trim|sanitize_string|lower',
        'slug'        => 'trim|sanitize_string|lower|slug',
        'path'        => 'trim|sanitize_string|lower',
        'keywords'    => 'trim|sanitize_string|lower',
        'description' => 'trim|sanitize_string',
        'title'       => 'trim|sanitize_string',
        'summary'     => 'trim|sanitize_string',
        'body'        => 'trim|sanitize_string',
        'scopes'      => 'trim|sanitize_string|lower',
        'category'    => 'trim|sanitize_string|lower',
        'tags'        => 'trim|sanitize_string|lower',
        'metadata'    => 'trim',
        'created'     => 'trim|sanitize_string',
        'published'   => 'trim|sanitize_string',
        'updated'     => 'trim|sanitize_string',
        'robots'      => 'trim|sanitize_numbers|whole_number',
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
        'robots'      => 'numeric',
    ];

    /**
     * Path in assets folder to user page images
     *
     * @var string $pageImagePath
     */
    protected $pageImagePath = '/img/pages/';

    /**
     * Default page image name
     *
     * @var string $pageImageFileName
     */
    protected $pageImageFileName = 'page.png';

    /**
     * Return the on-the-fly dynamic image generation URL path
     *
     * @param array $params params to url
     * @return string return the url path or false if not exists
     */
    public function pageImageUrlDynamic(array $params = [])
    {
        $f3 = \Base::instance();
        return Helpers\Url::internal($f3->alias('page_image', 'key=' . $params['key']), $params);
    }

    /**
     * Return the URL path to the image if exists or false
     *
     * @return string return the url path or false if not exists
     */
    public function pageImageUrlPath($filename = null): string
    {
        if (empty($filename)) {
            $filename = $this->pageImageFileName;
        }
        $assetsModel = Models\Assets::instance();
        return $assetsModel->assetUrlPath($this->pageImagePath . $this->uuid . '/' . $filename);
    }

    /**
     * Create if needed, and return the dir to the page image
     *
     * @return string $dir to the page image
     */
    public function pageImageDirPath(): string
    {
        $assetsModel = Models\Assets::instance();
        return $assetsModel->assetDirPath($this->pageImagePath . $this->uuid);
    }

    /**
     * Create if needed, and return the path to the page image
     *
     * @param null|string $filename filename for image
     * @return string $path to the page image
     */
    public function pageImageFilePath($filename = null): string
    {
        if (empty($filename)) {
            $filename = $this->pageImageFileName;
        }
        return $this->pageImageDirPath() . $filename;
    }

    /**
     * Return the URL path to the image if exists or false
     *
     * @return boolean $path to the page image
     * @return boolean true if the page image exists
     */
    public function pageImageExists($filename = null)
    {
        return file_exists($this->pageImageFilePath($filename));
    }

    /**
     * Return the URL path to the image if exists or false
     *
     * @param string $filename
     * @return false|string return the url path or false if not exists
     */
    public function pageImageUrl($filename = null)
    {
        $url = $this->pageImageExists($filename) ? $this->pageImageUrlPath($filename) : false;
        if (empty($url)) {
            return false;
        }
        return $url . '?' . filesize($this->pageImageFilePath($filename));
    }


    /**
     * Create page image from given file
     *
     * @param string $file path to file
     * @return boolean if the file was written and and asset record created
     */
    public function pageImageCreate($file)
    {
        if (!file_exists($file)) {
            throw new Exceptions\Exception('Page image creation file does not exist.');
        }
        $f3 = \Base::instance();

        // read exif metadata
        $reader = \PHPExif\Reader\Reader::factory(\PHPExif\Reader\Reader::TYPE_NATIVE);
        $exif = $reader->read($file);
        $metadata = $exif->getData();
        unset($exif);

        // load image
        $img = new \Image($file);

        // make sure maximum width/height not exceeded
        $max    = $f3->get('assets.image.max');
        $height = $img->height();
        $width  = $img->width();
        if ($width > $max['width'] || $height > $max['height']) {
            $height = $height > $max['height'] ? $max['height'] : $height;
            $width  = $width > $max['width'] ? $max['width'] : $width;
            $img->resize($width, $height);
        }

        // remove pre-existing cached-images
        $dirPath = $this->pageImageDirPath();
        foreach (glob($dirPath . '/*.jpg') as $file) {
            unlink($file);
        }

        // convert to .png, create new page image file, overwrites existing
        $pageImagePath = $this->pageImageFilePath();
        if (!$f3->write($pageImagePath, $img->dump('png', $f3->get('assets.image.default.quality.png')))) {
            return false;
        }

        // create asset table entry
        $asset = new Assets;

        // load pre existing asset
        $asset->load(['users_uuid = ? AND ' . $this->db->quoteKey('key') . ' = ? AND category = ?', $this->uuid, $this->key, 'page']);

        // set values
        $asset->users_uuid = $this->uuid;
        $asset->filename = $pageImagePath;
        $asset->name = $this-title;
        $asset->description = $this->description;
        $asset->size = filesize($pageImagePath);
        $asset->url = $this->url($this->pageImageUrl());
        $asset->type = 'image/png';
        $asset->key = $this->key;
        $asset->groups = 'public';
        $asset->category = 'page';
        $asset->tags = 'pages,page';
        $asset->metadata = json_encode($metadata, JSON_PRETTY_PRINT);

        return $asset->save();
    }
}
