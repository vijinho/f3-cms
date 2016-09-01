<?php

namespace FFCMS\Mappers;

use FFCMS\{Traits, Models};

use FFMVC\Helpers;

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
    use Traits\UrlHelper;

    /**
     * Fields and their visibility to clients, boolean or string of visible field name
     *
     * @var array $fieldsVisible
     */
    public $fieldsVisible = [
        'uuid'              => 'id',
        'password'          => false,
        'scopes'            => false,
        'login_count'       => false,
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

    /**
     * Path in assets folder to user profile images
     *
     * @var string $profileImagePath
     */
    protected $profileImagePath = '/img/users/';

    /**
     * Default profile image name
     *
     * @var string $profileImageFileName
     */
    protected $profileImageFileName = 'profile.png';

    /**
     * Return the on-the-fly dynamic image generation URL path
     *
     * @param array $params params to url
     * @return string return the url path or false if not exists
     */
    public function profileImageUrlDynamic(array $params = [])
    {
        $f3 = \Base::instance();
        return Helpers\Url::internal($f3->alias('user_image', 'key=profile,uuid=' . $this->uuid), $params);
    }

    /**
     * Return the URL path to the image if exists or false
     *
     * @return string return the url path or false if not exists
     */
    public function profileImageUrlPath($filename = null): string
    {
        if (empty($filename)) {
            $filename = $this->profileImageFileName;
        }
        $assetsModel = Models\Assets::instance();
        return $assetsModel->assetUrlPath($this->profileImagePath . $this->uuid . '/' . $filename);
    }

    /**
     * Create if needed, and return the dir to the user profile image
     *
     * @return string $dir to the profile image
     */
    public function profileImageDirPath(): string
    {
        $assetsModel = Models\Assets::instance();
        return $assetsModel->assetDirPath($this->profileImagePath . $this->uuid);
    }

    /**
     * Create if needed, and return the path to the user profile image
     *
     * @param null|string $filename filename for image
     * @return string $path to the profile image
     */
    public function profileImageFilePath($filename = null): string
    {
        if (empty($filename)) {
            $filename = $this->profileImageFileName;
        }
        return $this->profileImageDirPath() . $filename;
    }

    /**
     * Return the URL path to the image if exists or false
     *
     * @return boolean $path to the profile image
     * @return boolean true if the profile image exists
     */
    public function profileImageExists($filename = null)
    {
        return file_exists($this->profileImageFilePath($filename));
    }

    /**
     * Return the URL path to the image if exists or false
     *
     * @param string $filename
     * @return false|string return the url path or false if not exists
     */
    public function profileImageUrl($filename = null)
    {
        $url = $this->profileImageExists($filename) ? $this->profileImageUrlPath($filename) : false;
        if (empty($url)) {
            return false;
        }
        return $url . '?' . filesize($this->profileImageFilePath($filename));
    }


    /**
     * Create profile image from given file
     *
     * @param string $file path to file
     * @return boolean if the file was written and and asset record created
     */
    public function profileImageCreate($file)
    {
        if (!file_exists($file)) {
            throw new Exceptions\Exception('Profile image creation file does not exist.');
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
        $dirPath = $this->profileImageDirPath();
        foreach (glob($dirPath . '/*.jpg') as $file) {
            unlink($file);
        }

        // convert to .png, create new profile image file, overwrites existing
        $profileImagePath = $this->profileImageFilePath();
        if (!$f3->write($profileImagePath, $img->dump('png', $f3->get('assets.image.default.quality.png')))) {
            return false;
        }

        // create asset table entry
        $asset = new Assets;

        // load pre existing asset
        $asset->load(['users_uuid = ? AND ' . $this->db->quoteKey('key') . ' = ?', $this->uuid, 'profile']);

        // set values
        $asset->users_uuid = $this->uuid;
        $asset->filename = $profileImagePath;
        $asset->name = $this->firstname . ' ' . $this->lastname;
        $asset->description = $this->firstname . ' ' . $this->lastname . ' Profile Image';
        $asset->size = filesize($profileImagePath);
        $asset->url = $this->url($this->profileImageUrl());
        $asset->type = 'image/png';
        $asset->key = 'profile';
        $asset->groups = 'users';
        $asset->categories = 'profile';
        $asset->tags = 'users,profile';
        $asset->metadata = json_encode($metadata, JSON_PRETTY_PRINT);

        return $asset->save();
    }

    /**
     * @return string "firstname lastname"
     */
    public function fullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
