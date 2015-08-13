<?php

$dbns = $db->getTableName('news_sourses');
$dbni = $db->getTableName('news_items');

$db->sql = "SELECT * FROM $dbns WHERE ns_active = 1 ORDER BY ns_last_read LIMIT 1";
$db->exec();
$sourses = array();
while ($row = $db->fetch()) {
    $sourses[] = $row;
}

$rss_tags = array(
    'title',
    'link',
    'guid',
    'comments',
    'description',
    'pubDate',
    'category',
);

foreach ($sourses as $sourse) {
    $rssfeed = rss_to_array('item', $rss_tags, $sourse['ns_url']);
    foreach ($rssfeed as $item) {
        $datepub = date('Y-m-d H:i:s', strtotime($item['pubDate']));
        $item['title'] = isset($item['title']) ? $db->getEscapedString($item['title']) : '[no title]';
        $item['description'] = htmlentities($item['description'], ENT_QUOTES, 'UTF-8');
        $item['description'] = $db->getEscapedString($item['description']);
        $db->sql = "INSERT INTO $dbni
                        (ni_ns_id, ni_pubdate, ni_title, ni_url, ni_text, ni_active)
                    VALUES
                        (:ns_id, :pubdate, :title, :link, :text, 1)
                    ON DUPLICATE KEY UPDATE ni_title = :title2, ni_text = :text2";
        $db->execute(array(
            ':ns_id' => $sourse['ns_id'],
            ':pubdate' => date('Y-m-d H:i:s', strtotime($item['pubDate'])),
            ':title' => $item['title'],
            ':title2' => $item['title'],
            ':link' => $item['link'],
            ':text' => $item['description'],
            ':text2' => $item['description'],
        ));
    }
    $db->sql = "UPDATE $dbns SET ns_last_read = now() WHERE ns_id = :ns_id";
    $db->execute(array(
        ':ns_id' => $sourse['ns_id'],
    ));
}

$db->sql = "OPTIMIZE TABLE $dbns";
$db->exec();
$db->sql = "OPTIMIZE TABLE $dbni";
$db->exec();

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
