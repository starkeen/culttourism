<?php

$dbns = $db->getTableName('news_sourses');
$dbni = $db->getTableName('news_items');

$db->sql = "SELECT * FROM $dbns WHERE ns_active = 1 ORDER BY ns_last_read LIMIT 2";
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
        $item['title'] = mysql_real_escape_string($item['title']);
        $item['description'] = htmlentities($item['description'], ENT_QUOTES, 'UTF-8');
        $item['description'] = mysql_real_escape_string($item['description']);
        $db->sql = "INSERT INTO $dbni
                        (ni_ns_id, ni_pubdate, ni_title, ni_url, ni_text, ni_active)
                    VALUES
                        ('{$sourse['ns_id']}', '$datepub', '{$item['title']}', '{$item['link']}', '{$item['description']}', 1)
                    ON DUPLICATE KEY UPDATE ni_title = '{$item['title']}', ni_text = '{$item['description']}'";
        $db->exec();
    }
    $db->sql = "UPDATE $dbns SET ns_last_read = now() WHERE ns_id = '{$sourse['ns_id']}'";
    $db->exec();
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
                $items[$value] = $node->getElementsByTagName($value)->item(0)->nodeValue;
            }
            array_push($rss_array, $items);
        }
    } catch (Exception $e) {
        //echo 'Ошибка: ', $e->getMessage(), "\n";
        Logging::addHistory('cron', 'Ошибка получения новостей', $e->getMessage());
    }
    return $rss_array;
}

?>
