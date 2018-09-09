<?php

$ni = new MNewsItems($db);
$ns = new MNewsSources($db);

$rss_tags = array(
    'title',
    'link',
    'guid',
    'comments',
    'description',
    'pubDate',
    'category',
);

$sourses = $ns->getPortion();

foreach ($sourses as $sourse) {
    $rssfeed = rss_to_array('item', $rss_tags, $sourse['ns_url']);
    foreach ($rssfeed as $item) {
        $ni->add(array(
            'source_id' => $sourse['ns_id'],
            'pubdate' => date('Y-m-d H:i:s', strtotime($item['pubDate'])),
            'title' => !empty($item['title']) ? $item['title'] : '[no title]',
            'link' => $item['link'],
            'description' => htmlentities($item['description'] ?? '', ENT_QUOTES, 'UTF-8'),
        ));
    }
    $ns->updateByPk($sourse['ns_id'], array(
        'ns_last_read' => $ns->now(),
    ));
}

function rss_to_array($tag, $array, $url) {
    $doc = new DOMdocument();
    $rss_array = array();
    $items = array();
    try {
        if (!@$doc->load($url)) {
            throw new Exception("HTTP error [$url]");
        }
        foreach ($doc->getElementsByTagName($tag) AS $node) {
            foreach ($array as $key => $value) {
                $no = $node->getElementsByTagName($value);
                if (isset($no) && is_object($no)) {
                    if (is_object($no->item(0))) {
                        $items[$value] = $no->item(0)->nodeValue;
                    }
                }
            }
            array_push($rss_array, $items);
        }
    } catch (Exception $e) {
        echo 'Ошибка: ', $e->getMessage(), "\n";
        //Logging::addHistory('cron', 'Ошибка получения новостей', $e->getMessage());
    }
    return $rss_array;
}
