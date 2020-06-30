<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use SimpleXMLElement;

class Result
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @var int
     */
    private $pagesCount = 0;

    /**
     * @var SimpleXMLElement
     */
    private $xml;

    public function __construct(string $responseText)
    {
        $this->xml = new SimpleXMLElement($responseText);
    }

    public function getRequestId(): string
    {
        return (string) $this->xml->response->reqid;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        $errorCode = $this->getErrorCode();

        return $errorCode !== null;
    }

    public function getErrorText(): ?string
    {
        $errorNode = $this->getErrorNode();
        if ($errorNode === null) {
            return null;
        }

        return (string) $errorNode;
    }

    public function getErrorCode(): ?int
    {
        $errorNode = $this->getErrorNode();
        if ($errorNode === null) {
            return null;
        }

        $attributes = $errorNode->attributes();
        $errorCode = $attributes->code ?? null;

        if ($errorCode !== null) {
            $errorCode = (int) $errorCode;
        }

        return $errorCode;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getPagesCount(): int
    {
        return $this->pagesCount;
    }

    private function getErrorNode(): ?SimpleXMLElement
    {
        return $this->xml->response->error ?? null;
    }
}
