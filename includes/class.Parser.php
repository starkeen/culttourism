<?php

use PHPHtmlParser\Dom;

class Parser
{
    private $_curl = null;
    private $_text = null;
    private $_dom = null;
    private $_url = [];
    private $_purifier = null;
    private $_sites = [];
    private $_config = null;

    public function __construct($db, $url)
    {
        if (empty($url)) {
            throw new RuntimeException('Не передан URL');
        }
        $this->_sites = include _DIR_ROOT . '/config/config.parser.php';
        $this->_url = parse_url($url);
        $this->_url['domain'] = str_replace('www.', '', $this->_url['host']);
        $this->_config = $this->_sites[$this->_url['domain']];
        $this->_curl = new Curl($db);
        $this->_curl->setTTLDays(30);
        $this->_curl->setEncoding($this->_config['encoding']);

        $pconfig = HTMLPurifier_Config::createDefault();
        $pconfig->set('Core.Encoding', $this->_config['encoding']);
        $pconfig->set('HTML.Doctype', $this->_config['doctype']);
        $pconfig->set('URI.MakeAbsolute', true);
        $pconfig->set('HTML.Allowed', $this->_config['tagsallow']);
        $pconfig->set('URI.Base', $this->_url['scheme'] . '://' . $this->_url['host']);
        $pconfig->set('AutoFormat.AutoParagraph', true);
        $pconfig->set('Cache.DefinitionImpl', null);
        $pconfig->set('HTML.TidyLevel', 'heavy');

        $this->_purifier = new HTMLPurifier($pconfig);
        $text = $this->_curl->get($url);
        $this->_text = $this->_purifier->purify($text);

        $this->_dom = new DOMDocument('1.0', 'utf-8');
        $this->_dom->encoding = 'UTF-8';
        $encoded = $this->cleanXML($this->_text);
        @$this->_dom->loadHTML($encoded);
        $this->_dom->formatOutput = true;
        $this->_dom->preserveWhiteSpace = false;
        $this->_dom->normalizeDocument();
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        $out = [];
        //echo $this->_dom->saveHTML();
        $finder = new DomXPath($this->_dom);
        foreach ((array) $this->_config['list_items'] as $xpath) {
            $elements = $finder->query($xpath);
            if ($elements->length > 0) {
                foreach ($elements as $element) {
                    $item = [
                        'title' => $element->nodeValue,
                        'link' => $element->getAttribute('href'),
                        'xpath' => $element->getNodePath(),
                    ];
                    if ($item['link'] && $item['title']) {
                        $out[] = $item;
                    }
                }
            }
        }
        return $out;
    }

    /**
     * @return array
     */
    public function getItem(): array
    {
        $out = [
            'title' => '',
            'text' => '',
            'addr' => '',
            'phone' => '',
            'web' => '',
            'worktime' => '',
            'email' => '',
            'geo_latlon' => '',
            'geo_latlon_degmin' => '', // разделитель запятая N064 32.450, E040 30.860
            'geo_latlon_degmin1' => '', // разделитель пробел N48 44.49 E44 32.304
            'geo_latlon_degminsec' => '', // 51°20'4''N
            'geo_lat' => '',
            'geo_lon' => '',
            'geo_zoom' => 14,
        ];
        $meta = [];
        $replaces = [
            'from' => [
                '+7',
                '  ',
                'Адрес гостиницы:',
                '+38 (0',
                'Работает:',
                'Адрес:',
                'Координаты:',
                'Режим работы:',
                'Официальный сайт - ',
            ],
            'to' => [
                '',
                ' ',
                '',
                '+380 (',
                '',
                '',
                '',
                '',
                '',
            ],
        ];

        if (false && !empty($this->_config['use_parser']) && $this->_config['use_parser'] === 'php-html-parser') {
            $dom = new Dom();
            $dom->setOptions(
                [
                    'whitespaceTextNode' => false,
                ]
            );

            $dom->load($this->_dom->saveHTML());

            foreach ((array) $this->_config['item'] as $k => $item) {
                $data = [];
                foreach ((array) $item['parser'] as $parserConfig) {
                    $selector = $parserConfig['selector'] ?? null;
                    $index = $parserConfig['index'] ?? 0;
                    if ($selector !== null) {
                        $container = $dom->find($selector);
                        $data[] = $container[$index]->text;
                    }
                }

                $text_delimiter = $item['delimiter'] ?? '; ';
                $out[$k] = trim(
                    str_replace(
                        $replaces['from'],
                        $replaces['to'],
                        implode($text_delimiter, array_filter(array_unique($data, SORT_LOCALE_STRING)))
                    )
                );
            }
        } else {
            // echo $this->_dom->saveHTML();exit;
            $finder = new DomXPath($this->_dom);
            foreach ((array) $this->_config['item'] as $k => $item) {
                $data = [];
                foreach ((array) $item['path'] as $path) {
                    $elements = $finder->query($path);
                    if ($elements !== null) {
                        foreach ($elements as $element) {
                            if ((int) $item['type'] === 1) {
                                $data[] = trim(
                                    preg_replace('/\s+/', ' ', htmlspecialchars_decode($element->nodeValue))
                                );
                            } elseif ((int) $item['type'] === 2) {
                                $data[] = trim($element->getAttribute('href'));
                            }
                            $meta[$k][] = $element->getNodePath();
                        }
                    }
                }
                //asort($data);
                $text_delimiter = isset($item['delimiter']) ? $item['delimiter'] : '; ';
                $out[$k] = trim(
                    str_replace(
                        $replaces['from'],
                        $replaces['to'],
                        implode($text_delimiter, array_filter(array_unique($data, SORT_LOCALE_STRING)))
                    )
                );
                if ($k === 'geo_latlon') {
                    $out[$k] = trim(str_replace(', ', '', $out[$k]));
                    $out[$k] = mb_substr($out[$k], 0, mb_strpos($out[$k], ' '));
                }
            }
        }

        if (strpos($out['web'], 'redirect') !== false) {
            $data = parse_url($out['web']);
            $parts = [];
            parse_str($data['query'], $parts);
            $out['web'] = $parts['goto'];
        }
        $out['title'] = mb_strtoupper(mb_substr($out['title'], 0, 1, 'utf-8'), 'utf-8') . mb_substr(
                $out['title'],
                1,
                mb_strlen(
                    $out['title'],
                    'utf-8'
                ) - 1,
                'utf-8'
            );
        $out['text'] = nl2br(strip_tags(html_entity_decode($out['text'], ENT_QUOTES, 'utf-8')));
        if ($out['geo_latlon'] && mb_strpos($out['geo_latlon'], ',') !== false) {
            $latlon = explode(',', $out['geo_latlon']);
            $out['geo_lat'] = $latlon[0];
            $out['geo_lon'] = $latlon[1];
        }
        if ($out['geo_latlon_degmin'] && mb_strpos(trim($out['geo_latlon_degmin']), ',') !== false) {
            $latlon = explode(',', $out['geo_latlon_degmin']);
            $geo_lat = trim(str_replace('N', '', $latlon[0]));
            $geo_lon = trim(str_replace('E', '', $latlon[1]));
            $out['geo_lat'] = intval(substr($geo_lat, 0, strpos($geo_lat, ' ')));
            $out['geo_lon'] = intval(substr($geo_lon, 0, strpos($geo_lon, ' ')));
            $out['geo_lat'] += floatval(substr($geo_lat, strpos($geo_lat, ' ') + 1)) / 60;
            $out['geo_lon'] += floatval(substr($geo_lon, strpos($geo_lon, ' ') + 1)) / 60;
        }
        if ($out['geo_latlon_degmin1'] != '') {
            $latlon = trim($out['geo_latlon_degmin1'], '.');
            $matches = [];
            if (preg_match('/^N([0-9]*)\s(.*) E([0-9]*)\s(.*)/', $latlon, $matches)) {
                $out['geo_lat'] = intval($matches[1]);
                $out['geo_lon'] = intval($matches[3]);
                $out['geo_lat'] += floatval($matches[2]) / 60;
                $out['geo_lon'] += floatval($matches[4]) / 60;
            }
        }
        if ($out['geo_latlon_degminsec'] != '') {
            $latlon = trim($out['geo_latlon_degminsec']);
            $matches = [];
            if (preg_match("/^([0-9]*)°([0-9]*)'([0-9\.]*)''N, ([0-9]*)°([0-9]*)'([0-9\.]*)''E/", $latlon, $matches)) {
                $out['geo_lat'] = intval($matches[1]);
                $out['geo_lon'] = intval($matches[4]);
                $out['geo_lat'] += floatval($matches[2]) / 60;
                $out['geo_lon'] += floatval($matches[5]) / 60;
                $out['geo_lat'] += floatval($matches[3]) / 3600;
                $out['geo_lon'] += floatval($matches[6]) / 3600;
            }
        }
        //print_x($out);
        return $out;
    }

    protected function cleanXML($string)
    {
        $encoded = mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $encoded);
    }

}
