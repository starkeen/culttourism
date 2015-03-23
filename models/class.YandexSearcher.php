<?php

class YandexSearcher {

    private $_request_url = "https://xmlsearch.yandex.ru/xmlsearch?user=starkeen&key=03.10766361:bbf1bd34a06a8c93a745fcca95b31b80&l10n=ru&sortby=rlv&filter=strict";
    private $_meta = array(
        'page' => 0,
        'pages' => 20,
    );
    private $_enable_logging = false;
    private $_logger = null;
    private $_logger_id = null;

    public function search($request, $db = null) {
        $out = array(
            'found' => null,
            'results' => null,
            'pages_cnt' => null,
            'error' => null,
            'error_code' => null,
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
        if ($this->_enable_logging) {
            $this->_logger->add(array(
                'sl_query' => $request,
                'sl_request' => $doc,
            ));
        }
        $context = stream_context_create(array(
            'http' => array(
                'method' => "POST",
                'header' => "Content-type: application/xml\r\nContent-length: " . strlen($doc),
                'content' => $doc,
        )));
        $response = file_get_contents($this->_request_url, true, $context);
        if ($response) {
            if ($this->_enable_logging) {
                $this->_logger->setAnswer(array(
                    'sl_answer' => $response,
                ));
            }
            $xmldoc = new SimpleXMLElement($response);
            
            $out['error'] = $xmldoc->response->error;
            $out['found'] = $xmldoc->xpath("response/results/grouping/group/doc");
            if (empty($out['error'])) {
                $out['pages_cnt'] = (int) $xmldoc->response->found;
                foreach ($out['found'] as $item) {
                    $result_item = array(
                        'domain' => (string) $item->domain,
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
                $out['error_code'] = $out['error'];
                $out['error_text'] = "Ошибка: " . $out['error'];
                if ($this->_enable_logging) {
                    $this->_logger->setAnswer(array(
                        'sl_error_code' => 0,
                        'sl_error_text' => print_r($out['error'], 1),
                    ));
                }
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

    public function setPagesMax($max) {
        $this->_meta['pages'] = intval($max);
    }
    
    public function enableLogging($db) {
        $this->_logger = new MSearchLog($db);
        $this->_enable_logging = true;
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
