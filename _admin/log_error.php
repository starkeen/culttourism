<?php

require_once('common.php');

$smarty->assign('title', 'Настройки сайта');

$dbe = $db->getTableName('log_errors');

if (isset($_POST['clear_btn'])) {
    $sql = "TRUNCATE TABLE $dbe";
    $db->exec($sql);
}

$db->sql = "SELECT * FROM $dbe ORDER BY le_date";
$db->exec();
$records = [];
while ($row = $db->fetch()) {
    $records[$row['le_id']] = $row;
}
$smarty->assign('records', $records);

$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/errorlog.tpl'));
$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
