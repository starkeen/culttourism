<?php

require_once('common.php');

$smarty->assign('title', 'Настройки сайта');

$sp = new MSysProperties($db);

if (isset($_GET['rid']) && intval($_GET['rid']) != 0) {
    $dbs = $db->getTableName('siteprorerties');
    $rid = cut_trash_int($_GET['rid']);

    if (isset($_POST) && !empty($_POST)) {
        foreach ($_POST['param'] as $sid => $sval) {
            $sp->updateByPk(intval($sid), array(
                'sp_value' => htmlentities(cut_trash_text($sval), ENT_QUOTES, 'UTF-8'),
            ));
        }
        header('location: settings.php');
        exit();
    }

    $db->sql = "SELECT sp_id, sp_name, sp_value, sp_title, sp_whatis
                FROM $dbs
                WHERE sp_title != ''
                AND sp_rs_id = '$rid'";
    $db->exec();
    $settings = array();
    while ($row = $db->fetch()) {
        $settings[$row['sp_id']] = $row;
    }
    $smarty->assign('setts', $settings);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/refs.settings.sm.html'));
} else {
    $dbrs = $db->getTableName('ref_siteprop');
    $db->sql = "SELECT rs_id, rs_title FROM $dbrs ORDER BY rs_id";
    $db->exec();
    $reflist = array();
    while ($row = $db->fetch()) {
        $reflist[$row['rs_id']] = $row['rs_title'];
    }
    $smarty->assign('reflist', $reflist);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/refs.list.sm.html'));
}
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
