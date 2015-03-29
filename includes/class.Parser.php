<?php

require_once _DIR_ADDONS . '/htmlpurifier-4.6.0/library/HTMLPurifier.auto.php';

class Parser {

    private $_curl = null;
    private $_text = null;
    private $_dom = null;
    private $_url = array();
    private $_purifier = null;
    private $_sites = array();
    private $_config = null;

    public function __construct($db, $url) {
        if ($url == '') {
            exit;
        }
        $this->_sites = include _DIR_ROOT . '/config/config.parser.php';
        $this->_url = parse_url($url);
        $this->_url['domain'] = str_replace('www.', '', $this->_url['host']);
        $this->_config = $this->_sites[$this->_url['domain']];
        $this->_curl = new Curl($db);

        $pconfig = HTMLPurifier_Config::createDefault();
        $pconfig->set('Core.Encoding', 'UTF-8');
        $pconfig->set('HTML.Doctype', $this->_config['doctype']);
        $pconfig->set('URI.MakeAbsolute', true);
        $pconfig->set('HTML.Allowed', 'h1,div[class][id],p[class][id],a[href][class],a[class][href]');
        $pconfig->set('URI.Base', $this->_url['scheme'] . '://' . $this->_url['host']);
        $pconfig->set('AutoFormat.AutoParagraph', true);
        $pconfig->set('Cache.DefinitionImpl', null);
        $pconfig->set('HTML.TidyLevel', 'heavy');
        $this->_purifier = new HTMLPurifier($pconfig);
        $text = $this->_curl->get($url);
        $this->_text = $this->_purifier->purify($text);

        $this->_dom = new DOMDocument('1.0', 'utf-8');
        $this->_dom->encoding = 'utf-8';
        $this->_dom->loadHTML(mb_convert_encoding($this->_text, 'HTML-ENTITIES', 'UTF-8'));
        $this->_dom->formatOutput = true;
        $this->_dom->preserveWhiteSpace = FALSE;
        $this->_dom->normalizeDocument();
    }

    public function getList() {
        $out = array();
        $finder = new DomXPath($this->_dom);
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
        $meta = array();
        $finder = new DomXPath($this->_dom);
        foreach ($this->_config['item'] as $k => $item) {
            $data = array();
            foreach ($item['path'] as $path) {
                $elements = $finder->query($path);
                if (!is_null($elements)) {
                    foreach ($elements as $element) {
                        if ($item['type'] == 1) {
                            $data[] = trim($element->nodeValue);
                        } elseif ($item['type'] == 2) {
                            $data[] = $element->getAttribute('href');
                        }
                        $meta[$k][] = $element->getNodePath();
                    }
                }
            }
            asort($data);
            $out[$k] = str_replace('  ', ' ', implode('; ', array_unique($data, SORT_LOCALE_STRING)));
        }
        if (strpos($out['web'], 'redirect') !== false) {
            $data = parse_url($out['web']);
            $parts = array();
            parse_str($data['query'], $parts);
            $out['web'] = $parts['goto'];
        }
        $out['text'] = strip_tags(html_entity_decode($out['text'], ENT_QUOTES, 'utf-8'));
        return $out;
    }

}
