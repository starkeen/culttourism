<?php

require_once 'common.php';

$smarty->assign('title', 'Статистика поиска');

$dbpc = $db->getTableName('pagecity');
$dbur = $db->getTableName('region_url');
$dbsc = $db->getTableName('search_cache');

if (isset($_GET['filter']) && $_GET['filter'] === 'free') {
    $where = 'AND url.url IS NULL';
} else {
    $where = '';
}

$db->sql = "SELECT count(*) cnt, sc_query, url.url
            FROM $dbsc sc
            LEFT JOIN $dbpc pc ON pc.pc_title = sc.sc_query
            LEFT JOIN $dbur url ON url.uid = pc.pc_url_id
            WHERE sc.sc_session != '2'
            $where
            GROUP BY sc_query
            HAVING cnt>1
            ORDER BY cnt DESC, sc_query";
$db->exec();
$stat = [];
while ($row = $db->fetch()) {
    $stat[$row['sc_query']] = $row;
}
$smarty->assign('requests', $stat);
$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/stat_search.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
