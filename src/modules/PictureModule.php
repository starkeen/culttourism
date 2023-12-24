<?php

declare(strict_types=1);

namespace app\modules;

use app\constant\MimeType;
use app\core\exception\CoreException;
use app\core\module\ModuleInterface;
use app\core\SiteRequest;
use app\core\SiteResponse;
use app\core\WebUser;
use app\db\MyDB;
use app\exceptions\NotFoundException;
use app\model\repository\PhotosRepository;

class PictureModule implements ModuleInterface
{
    private MyDB $db;

    private WebUser $webUser;

    private ?PhotosRepository $photosRepository = null;

    public function __construct(MyDB $db, WebUser $webUser)
    {
        $this->db = $db;
        $this->webUser = $webUser;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(SiteRequest $request): bool
    {
        return $request->getModuleKey() === 'picture';
    }

    /**
     * @param  SiteRequest  $request
     * @param  SiteResponse $response
     * @throws NotFoundException
     */
    public function handle(SiteRequest $request, SiteResponse $response): void
    {
        $method = $request->getLevel1();

        if ($method === 'share') {
            $pictureId = (int) $request->getLevel2();

            if ($pictureId === 0) {
                throw new NotFoundException();
            }

            $photo = $this->getPhotosRepository()->getItemByPk($pictureId);
            if ($photo === null) {
                throw new NotFoundException();
            }

            $resultImage = imagecreatetruecolor(1200, 900);
            if ($photo->ph_mime === MimeType::JPEG) {
                $sourceImage = imagecreatefromjpeg(GLOBAL_DIR_ROOT . $photo->ph_src);

            } elseif ($photo->ph_mime === MimeType::PNG) {
                $sourceImage = imagecreatefrompng(GLOBAL_DIR_ROOT . $photo->ph_src);
            } else {
                throw new CoreException('Неподдерживаемый тип: ' . $photo->ph_mime);
            }
            imagecopyresampled($resultImage, $sourceImage, 0, 0, 0, 0, 1200, 900, $photo->ph_width, $photo->ph_height);
            $response->getHeaders()->add('Content-Type: image/jpeg');
            imagejpeg($resultImage);
            imagedestroy($resultImage);
        } else {
            throw new NotFoundException();
        }
    }

    private function getPhotosRepository(): PhotosRepository
    {
        if ($this->photosRepository === null) {
            $this->photosRepository = new PhotosRepository($this->db);
        }
        return $this->photosRepository;
    }
}
