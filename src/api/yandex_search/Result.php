<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use SimpleXMLElement;

class Result
{
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
     * @return ResultItem[]
     */
    public function getItems(): array
    {
        $result = [];

        $foundDocs = $this->xml->xpath('response/results/grouping/group/doc');

        foreach ($foundDocs as $item) {
            /** @var SimpleXMLElement $item */
            if ($item->title === null) {
                continue;
            }
            $itemDescriptionElement = $item->passages->passage ?? new SimpleXMLElement('<x/>');
            $resultItem = new ResultItem();
            $resultItem->setTitle($this->highlight($item->title));
            $resultItem->setDescription($this->clean($itemDescriptionElement));
            $resultItem->setUrl((string) $item->url);
            $result[] = $resultItem;
        }

        return $result;
    }

    public function getHumanResolution(): string
    {
        return (string) $this->xml->response->results->grouping->{'found-docs-human'};
    }

    /**
     * @return int
     */
    public function getPagesCount(): int
    {
        return (int) ceil($this->getDocumentsCount() / $this->getDocumentsPerPage());
    }

    public function getDocumentsCount(): int
    {
        return (int) $this->xml->response->results->grouping->{'found-docs'}[0];
    }

    public function getDocumentsPerPage(): int
    {
        return 15;
    }

    private function getErrorNode(): ?SimpleXMLElement
    {
        return $this->xml->response->error ?? null;
    }

    /**
     * Добавление тегов strong в подсветку
     *
     * @param SimpleXMLElement $node
     *
     * @return string
     */
    private function highlight(SimpleXMLElement $node): string
    {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '</strong>', preg_replace('/<hlword[^>]*>/', '<strong>', $stripped));
    }

    /**
     * Очистка строки XML от некоторых тегов
     *
     * @param SimpleXMLElement $node
     *
     * @return string
     */
    private function clean(SimpleXMLElement $node): string
    {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '', preg_replace('/<hlword[^>]*>/', '', $stripped));
    }
}
