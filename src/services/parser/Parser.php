<?php

declare(strict_types=1);

namespace app\services\parser;

use app\db\MyDB;
use Curl;
use DOMDocument;
use DOMXPath;
use HTMLPurifier;
use HTMLPurifier_Config;
use InvalidArgumentException;

class Parser
{
    private $dom;
    private $config;

    public function __construct(MyDB $db, string $url)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Не передан URL');
        }

        $sites = include_once GLOBAL_DIR_ROOT . '/config/config.parser.php';
        if ($sites === true) {
            throw new ParserException('Не найден файл конфигурации парсера');
        }

        $urlDetails = parse_url($url);
        $urlDetails['domain'] = str_replace('www.', '', $urlDetails['host']);
        $this->config = $sites[$urlDetails['domain']];

        $curl = new Curl($db);
        $curl->setTTLDays(30);
        $curl->setEncoding($this->config['encoding']);

        $purifierConfig = HTMLPurifier_Config::createDefault();
        $purifierConfig->set('Core.Encoding', $this->config['encoding']);
        $purifierConfig->set('HTML.Doctype', $this->config['doctype']);
        $purifierConfig->set('URI.MakeAbsolute', true);
        $purifierConfig->set('HTML.Allowed', $this->config['tagsallow']);
        $purifierConfig->set('URI.Base', $urlDetails['scheme'] . '://' . $urlDetails['host']);
        $purifierConfig->set('AutoFormat.AutoParagraph', true);
        $purifierConfig->set('Cache.DefinitionImpl', null);
        $purifierConfig->set('HTML.TidyLevel', 'heavy');

        $purifier = new HTMLPurifier($purifierConfig);
        $rawText = $curl->get($url);
        $cleanText = $purifier->purify($rawText);

        $this->dom = new DOMDocument('1.0', 'utf-8');
        $this->dom->encoding = 'UTF-8';
        $encoded = $this->cleanXML($cleanText);
        @$this->dom->loadHTML($encoded);
        $this->dom->formatOutput = true;
        $this->dom->preserveWhiteSpace = false;
        $this->dom->normalizeDocument();
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        $out = [];
        $finder = new DomXPath($this->dom);
        foreach ((array) $this->config['list_items'] as $xpath) {
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

        $finder = new DomXPath($this->dom);
        foreach ((array) $this->config['item'] as $k => $item) {
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
            $text_delimiter = $item['delimiter'] ?? '; ';
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
            $out['geo_lat'] = (int) substr($geo_lat, 0, strpos($geo_lat, ' '));
            $out['geo_lon'] = (int) substr($geo_lon, 0, strpos($geo_lon, ' '));
            $out['geo_lat'] += (float) substr($geo_lat, strpos($geo_lat, ' ') + 1) / 60;
            $out['geo_lon'] += (float) substr($geo_lon, strpos($geo_lon, ' ') + 1) / 60;
        }
        if ($out['geo_latlon_degmin1'] != '') {
            $latlon = trim($out['geo_latlon_degmin1'], '.');
            $matches = [];
            if (preg_match('/^N(\d*)\s(.*) E(\d*)\s(.*)/', $latlon, $matches)) {
                $out['geo_lat'] = (int) $matches[1];
                $out['geo_lon'] = (int) $matches[3];
                $out['geo_lat'] += (float) $matches[2] / 60;
                $out['geo_lon'] += (float) $matches[4] / 60;
            }
        }
        if ($out['geo_latlon_degminsec'] != '') {
            $latlon = trim($out['geo_latlon_degminsec']);
            $matches = [];
            if (preg_match("/^(\d*)°(\d*)'([0-9\.]*)''N, (\d*)°(\d*)'([0-9\.]*)''E/", $latlon, $matches)) {
                $out['geo_lat'] = (int) $matches[1];
                $out['geo_lon'] = (int) $matches[4];
                $out['geo_lat'] += (float) $matches[2] / 60;
                $out['geo_lon'] += (float) $matches[5] / 60;
                $out['geo_lat'] += (float) $matches[3] / 3600;
                $out['geo_lon'] += (float) $matches[6] / 3600;
            }
        }

        return $out;
    }

    /**
     * @param  string $string
     * @return string
     */
    protected function cleanXML(string $string): string
    {
        $encoded = mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');

        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $encoded);
    }
}
