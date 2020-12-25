<?php

require_once('common.php');

$smarty->assign('title', 'Настройки сайта');

$dbe = $db->getTableName('log_errors');

if (isset($_POST['clear_btn'])) {
    $sql = "TRUNCATE TABLE $dbe";
    $res = $db->exec($sql);
}

$db->sql = "SELECT * FROM $dbe ORDER BY le_date";
$res = $db->exec();
$records = array();
while ($row = $db->fetch($res)) {
    $records[$row['le_id']] = $row;
}
$smarty->assign('records', $records);

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/errorlog.tpl'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
