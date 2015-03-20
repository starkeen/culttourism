<?php

class YandexSearcher {

    private $_request_url = "https://xmlsearch.yandex.ru/xmlsearch?user=starkeen&key=03.10766361:bbf1bd34a06a8c93a745fcca95b31b80&l10n=ru&sortby=rlv&filter=strict";
    private $_meta = array(
        'page' => 0,
        'pages' => 20,
    );

    public function search($request) {
        $out = array(
            'found' => null,
            'results' => null,
            'pages_cnt' => null,
            'error' => null,
            'error_text' => null,
        );
        $doc = <<<DOC
<?xml version="1.0" encoding="utf-8"?>
<request>
        <query>$request</query>
        <page>{$this->_meta['page']}</page>
        <sortby order="descending" priority="no">rlv</sortby>
        <maxpassages>5</maxpassages>
        <groupings>
            <groupby attr="" mode="flat" groups-on-page="{$this->_meta['pages']}" docs-in-group="1" curcateg="-1" />
        </groupings>
</request>
DOC;
        $context = stream_context_create(array(
            'http' => array(
                'method' => "POST",
                'header' => "Content-type: application/xml\r\nContent-length: " . strlen($doc),
                'content' => $doc,
        )));
        $response = file_get_contents($this->_request_url, true, $context);
        if ($response) {
            $xmldoc = new SimpleXMLElement($response);
            $out['error'] = $xmldoc->response->error;
            $out['found'] = $xmldoc->xpath("response/results/grouping/group/doc");
            if (empty($out['error'])) {
                $out['pages_cnt'] = (int) $xmldoc->response->found;
                foreach ($out['found'] as $item) {
                    $result_item = array(
                        'url' => (string) $item->url,
                        'title' => $this->clean($item->title),
                        'title_hw' => $this->highlight($item->title),
                        'descr' => '',
                        'descr_hw' => '',
                    );
                    if ($item->passages) {
                        foreach ($item->passages->passage as $passage) {
                            $result_item['descr'] .= $this->clean($passage) . "\n";
                            $result_item['descr_hw'] .= $this->highlight($passage);
                        }
                    }
                    $result_item['descr'] = trim($result_item['descr']);
                    $result_item['descr_hw'] = trim($result_item['descr_hw']);
                    $out['results'][] = $result_item;
                }
            } else {
                $out['error_text'] = "Ошибка: " . $out['error'];
            }
        } else {
            $out['error_text'] = "Внутренняя ошибка сервера.\n";
        }
        unset($out['found']);
        unset($out['error']);
        return $out;
    }

    public function setPage($page) {
        $this->_meta['page'] = intval($page);
    }

    private function highlight($node) {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '</strong>', preg_replace('/<hlword[^>]*>/', '<strong>', $stripped));
    }

    private function clean($node) {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '', preg_replace('/<hlword[^>]*>/', '', $stripped));
    }

}
