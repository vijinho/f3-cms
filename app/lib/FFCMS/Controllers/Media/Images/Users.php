<?php

namespace FFCMS\Controllers\Media\Images;

use FFCMS\Mappers;

/**
 * Index Controller Class.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2016 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class Users
{
    /**
     *
     *
     * @param \Base $f3
     * @param array $params
     * @return void
     */
    public function profile(\Base $f3, array $params = [])
    {
        // get asset table entry
        $asset = new Mappers\Assets;
        $asset->load(['users_uuid = ? AND ' . $asset->quotekey('key') . ' = ?', $params['uuid'], $params['key']]);
        if (null === $asset->uuid) {
            return $f3->status(404);
        }

        // get image dimensions
        $max    = $f3->get('assets.image.max');
        $height = abs((int) $f3->get('REQUEST.height'));
        $width  = abs((int) $f3->get('REQUEST.width'));

        if (empty($height) || empty($width)) {
            // resize on longest side
            if ($height > $width) {
                $width = $height;
            } elseif ($width > $height) {
                $height = $width;
            } else {
                // use default height/width if missing
                $height = $f3->get('assets.image.default.height');
                $width  = $f3->get('assets.image.default.width');
            }
        } elseif ($width > $max['width'] || $height > $max['height']) {
            // make sure maximum width/height not exceeded
            $height = $height > $max['height'] ? $max['height'] : $height;
            $width  = $width > $max['width'] ? $max['width'] : $width;
        }

        // load user mapper
        $usersMapper = new Mappers\Users;
        $usersMapper->load($params['uuid']);

        // work out filename
        $hash     = $f3->hash($asset); // generate unique hash for asset
        $filename = 'profile_' . $width . 'x' . $height . '.jpg';

        // return the url if exists
        $url      = $usersMapper->profileImageUrlPath($filename);
        if (false !== $url) {
            return $f3->reroute($url);
        }

        // 404 if the original asset file does not exist
        if (!file_exists($asset->filename)) {
            return $f3->status(404);
        }

        // create new resized file
        $img      = new \Image($asset->filename);
        $img->resize($width, $height);
        if (!$f3->write($usersMapper->profileImageFilePath($filename), $img->dump('jpeg', $f3->get('assets.image.default.quality.jpg')))) {
            return $f3->status(404);
        }

        // serve the generated image via the web
        return $f3->reroute($url);
    }
}
