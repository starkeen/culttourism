<?php

declare(strict_types=1);

namespace app\services\image_storage;

use app\db\FactoryDB;
use MPhotos;

class ImageStorageFactory
{
    public static function build(): ImageStorageService
    {
        $db = FactoryDB::db();
        $model = new MPhotos($db);

        return new ImageStorageService(_DIR_TMP, _DIR_PHOTOS, $model);
    }
}
