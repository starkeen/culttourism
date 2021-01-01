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

        return new ImageStorageService(GLOBAL_DIR_TMP, GLOBAL_DIR_PHOTOS, $model);
    }
}
