<?php

declare(strict_types=1);

namespace app\api\google_search;

use app\constant\MimeType;
use InvalidArgumentException;

class ResultImage
{
    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var int
     */
    private $height;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $byteSize;

    /**
     * @var string
     */
    private $thumbnailLink;

    /**
     * @var int
     */
    private $thumbnailHeight;

    /**
     * @var int
     */
    private $thumbnailWidth;

    /**
     * @var string
     */
    private $contextLink;

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getByteSize(): int
    {
        return $this->byteSize;
    }

    /**
     * @param int $byteSize
     */
    public function setByteSize(int $byteSize): void
    {
        $this->byteSize = $byteSize;
    }

    /**
     * @return string
     */
    public function getThumbnailLink(): string
    {
        return $this->thumbnailLink;
    }

    /**
     * @param string $thumbnailLink
     */
    public function setThumbnailLink(string $thumbnailLink): void
    {
        $this->thumbnailLink = $thumbnailLink;
    }

    /**
     * @return int
     */
    public function getThumbnailHeight(): int
    {
        return $this->thumbnailHeight;
    }

    /**
     * @param int $thumbnailHeight
     */
    public function setThumbnailHeight(int $thumbnailHeight): void
    {
        $this->thumbnailHeight = $thumbnailHeight;
    }

    /**
     * @return int
     */
    public function getThumbnailWidth(): int
    {
        return $this->thumbnailWidth;
    }

    /**
     * @param int $thumbnailWidth
     */
    public function setThumbnailWidth(int $thumbnailWidth): void
    {
        $this->thumbnailWidth = $thumbnailWidth;
    }

    /**
     * @return string
     */
    public function getContextLink(): string
    {
        return $this->contextLink;
    }

    /**
     * @param string $contextLink
     */
    public function setContextLink(string $contextLink): void
    {
        $this->contextLink = $contextLink;
    }

    public function getImageType(): string
    {
        $mimeValue = $this->getMimeType();
        if (!MimeType::isValid($mimeValue)) {
            throw new InvalidArgumentException('Неизвестный mime-type: ' . $mimeValue);
        }
        $mimeTypeObject = new MimeType($mimeValue);

        return $mimeTypeObject->getDefaultExtension();
    }
}
