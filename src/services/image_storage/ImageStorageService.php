<?php

declare(strict_types=1);

namespace app\services\image_storage;

use app\constant\MimeType;
use MPhotos;
use RuntimeException;

class ImageStorageService
{
    /**
     * @var string - директория временных файлов без слеша на конце
     */
    private $directoryTmp;

    /**
     * @var string - директория хранения файлов
     */
    private $photosDirectory;

    /**
     * @var MPhotos
     */
    private $photosModel;

    public function __construct(string $tmpDir, string $photosDirectory, MPhotos $photosModel)
    {
        $this->directoryTmp = $tmpDir;
        $this->photosDirectory = $photosDirectory;
        $this->photosModel = $photosModel;
    }

    public function uploadFromUrl(string $url, string $origin = null, string $title = null, string $author = null): int
    {
        $uploadedFileName = $this->downloadTmp($url);
        $uploadedFilePath = $this->getTemporaryFilePath($uploadedFileName);
        $fileHash = md5_file($uploadedFilePath);
        $mime = mime_content_type($uploadedFilePath);
        $mimeType = new MimeType($mime);
        $fileExt = $mimeType->getDefaultExtension();
        $fileName = $fileHash . '.' . $fileExt;

        $targetDirectory = $this->getTargetDirectoryName($fileName);
        $fileSrc = '/data/photos' . $targetDirectory . '/' . $fileName;
        $filePath = $this->photosDirectory . $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        copy($uploadedFilePath, $filePath);
        $size = getimagesize($filePath);
        [$imgWidth, $imgHeight] = $size;
        $weight = filesize($filePath);
        $mime = mime_content_type($filePath);

        $id = $this->photosModel->insert(
            [
                'ph_title' => $title,
                'ph_author' => $author,
                'ph_link' => $origin,
                'ph_src' => $fileSrc,
                'ph_weight' => $weight,
                'ph_width' => $imgWidth,
                'ph_height' => $imgHeight,
                'ph_mime' => $mime,
                'ph_lat' => null,
                'ph_lon' => null,
                'ph_pc_id' => null,
                'ph_pt_id' => null,
                'ph_date_add' => $this->photosModel->now(),
                'ph_order' => 20,
            ]
        );

        $this->deleteTmp($uploadedFileName);

        return (int) $id;
    }

    public function bindPhotoToObject(int $photoId, int $objectId): void
    {
        $this->photosModel->updateByPk(
            $photoId,
            [
                'ph_pt_id' => $objectId,
            ]
        );
    }

    private function downloadTmp(string $url): string
    {
        $pathHash = md5($url);
        $resultPath = $this->getTemporaryFilePath($pathHash);

        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];
        $context = stream_context_create($contextOptions);

        file_put_contents($resultPath, fopen($url, 'rb', false, $context));

        return $pathHash;
    }

    private function deleteTmp(string $fileName): bool
    {
        $resultPath = $this->getTemporaryFilePath($fileName);

        return unlink($resultPath);
    }

    private function getTemporaryFilePath(string $fileName): string
    {
        return $this->directoryTmp . DIRECTORY_SEPARATOR . $fileName;
    }

    private function getTargetDirectoryName(string $fileName): string
    {
        $directoryLevel1 = DIRECTORY_SEPARATOR . $fileName[0];
        $concurrentDirectory = $this->photosDirectory . $directoryLevel1;
        if (!file_exists($concurrentDirectory)) {
            if (!mkdir($concurrentDirectory, 0700, true)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        $directoryLevel2 = $directoryLevel1 . DIRECTORY_SEPARATOR . $fileName[1];
        $concurrentDirectory = $this->photosDirectory . $directoryLevel2;
        if (!file_exists($concurrentDirectory)) {
            if (!mkdir($concurrentDirectory, 0700, true)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        return $directoryLevel2;
    }
}
