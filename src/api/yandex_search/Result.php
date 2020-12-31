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
            $itemDescription = '';
            $itemPassages = $item->passages->passage ?? null;
            if ($itemPassages !== null) {
                $itemDescriptionElements = [];
                foreach ($itemPassages as $passage) {
                    $itemDescriptionElements[] = $this->clean($passage);
                }
                $itemDescription = implode('&hellip; ', $itemDescriptionElements);
            }
            $itemDescriptionElement = $item->headline ?? null;
            if ($itemDescription === '' && $itemDescriptionElement !== null) {
                $itemDescription = $this->clean($itemDescriptionElement);
            }
            $resultItem = new ResultItem();
            $resultItem->setTitle($this->highlight($item->title));
            $resultItem->setUrl((string) $item->url);
            $resultItem->setDomain((string) $item->domain);
            $resultItem->setDescription($itemDescription);

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
        $total = $this->getDocumentsCount();
        $perPage = $this->getDocumentsPerPage();

        return (int) ceil($total / $perPage);
    }

    public function getDocumentsCount(): int
    {
        return (int) $this->xml->response->results->grouping->{'found-docs'}[0];
    }

    public function getDocumentsPerPage(): int
    {
        return (int) $this->xml->response->results->grouping['groups-on-page'];
    }

    public function getCorrection(): ?ResultCorrection
    {
        $result = null;

        $misspell = $this->xml->response->misspell ?? null;

        if ($misspell !== null) {
            $result = new ResultCorrection((string) $misspell->rule);
            $result->setSourceText((string) $misspell->{'source-text'});
            $result->setResultText((string) $misspell->text);
        }

        return $result;
    }

    public function getString() :string
    {
        return $this->xml->asXML();
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
        $stripped = preg_replace('/<\/?(title|passage|headline)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '', preg_replace('/<hlword[^>]*>/', '', $stripped));
    }
}
