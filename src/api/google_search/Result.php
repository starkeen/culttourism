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
            // title
            //
            $resultItem = new ResultItem();
            $result[] = $resultItem;
        }

        return $result;
    }
}
