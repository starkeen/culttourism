<?php

require_once _DIR_ADDONS . '/htmlpurifier-4.6.0/library/HTMLPurifier.auto.php';

class Parser {

    private $_curl = null;
    private $_text = null;
    private $_url = array();
    private $_purifier = null;
    private $_sites = array(
        'komandirovka.ru' => array(
            'encoding' => 'utf-8',
            'doctype' => 'XHTML 1.0 Transitional',
            'list_items' => array(
                "//div[@class='ajax_objects']/div/div/div[@class='detail_h']/a[1]", //приоритетные
                "//div[@class='ajax_objects']/div/a[1]", //топ
                "//div[@class='ajax_objects']/div/div/a[1]", //обычные
            ),
        ),
    );
    private $_config = null;

    public function __construct($db, $url) {
        $this->_url = parse_url($url);
        $this->_url['domain'] = str_replace('www.', '', $this->_url['host']);
        $this->_config = $this->_sites[$this->_url['domain']];
        $this->_curl = new Curl($db);

        $pconfig = HTMLPurifier_Config::createDefault();
        $pconfig->set('Core.Encoding', 'UTF-8');
        $pconfig->set('HTML.Doctype', $this->_config['doctype']);
        $pconfig->set('URI.MakeAbsolute', true);
        $pconfig->set('HTML.Allowed', 'div[class][id],p[class][id],a[href][class][id]');
        $pconfig->set('URI.Base', $this->_url['scheme'] . '://' . $this->_url['host']);
        $pconfig->set('AutoFormat.AutoParagraph', true);
        $pconfig->set('Cache.DefinitionImpl', null);
        $pconfig->set('HTML.TidyLevel', 'heavy');
        $this->_purifier = new HTMLPurifier($pconfig);
        $text = $this->_curl->get($url);
        $this->_text = $this->_purifier->purify($text);

        //$this->_text = $this->_curl->get($url);
    }

    public function getList() {
        $out = array();

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->encoding = 'utf-8';
        $dom->loadHTML(mb_convert_encoding($this->_text, 'HTML-ENTITIES', 'UTF-8'));
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = FALSE;
        $dom->normalizeDocument();

        $finder = new DomXPath($dom);
        foreach ($this->_config['list_items'] as $xpath) {
            $elements = $finder->query($xpath);
            if (!is_null($elements)) {
                foreach ($elements as $element) {
                    $out[] = array(
                        'title' => $element->nodeValue,
                        'link' => $element->getAttribute('href'),
                        'xpath' => $element->getNodePath(),
                    );
                }
            }
        }
        return $out;
    }

    public function getItem() {
        $out = array(
            'title' => '',
            'type_title' => '',
            'text' => '',
            'addr' => '',
            'phone' => '',
            'web' => '',
            'worktime' => '',
            'email' => '',
            'geo_lat' => '',
            'geo_lon' => '',
            'geo_zoom' => '',
        );
        return $out;
    }

}
