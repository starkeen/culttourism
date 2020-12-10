<?php

declare(strict_types=1);

namespace app\api\google_search;

class Result
{
    /**
     * @var string
     */
    private $responseBody;

    public function __construct(string $responseBody)
    {
        $this->responseBody = $responseBody;
    }

    /**
     * @return ResultItem[]
     */
    public function getItems(): array
    {
        $result = [];
        $responseData = json_decode($this->responseBody);
        $items = $responseData->items ?? [];

        foreach ($items as $item) {
            $resultItem = new ResultItem();
            $resultItem->setTitle($item->title ?? 'untitled');
            $resultItem->setUrl($item->link);
            $resultItem->setDomain($item->displayLink);
            if (isset($item->snippet)) {
                $resultItem->setDescription($item->snippet);
            }
            if (isset($item->mime)) {
                $resultItem->setMimeType($item->mime);
            }
            if (isset($item->image)) {
                $imageData = new ResultImage();
                $imageData->setMimeType($item->mime);
                $imageData->setHeight($item->image->height);
                $imageData->setWidth($item->image->width);
                $imageData->setByteSize($item->image->byteSize);
                $imageData->setContextLink($item->image->contextLink);
                $imageData->setThumbnailLink($item->image->thumbnailLink);
                $imageData->setThumbnailHeight($item->image->thumbnailHeight);
                $imageData->setThumbnailWidth($item->image->thumbnailWidth);

                $resultItem->setImageData($imageData);
            }

            $result[] = $resultItem;
        }

        return $result;
    }
}
