<?php

require_once('common.php');

$smarty->assign('title', 'Счетчики на сайте');

$dbc = $db->getTableName('counters');

if (!isset($_GET['cid'])) {//==================================================== СПИСОК
    $db->sql = "SELECT dbc.*, DATE_FORMAT(dbc.cnt_datefrom, '%Y-%m-%d') AS datefrom
                FROM $dbc dbc
                ORDER BY dbc.cnt_sort";
    $db->exec();
    while ($row = $db->fetch()) {
        $list[$row['cnt_id']] = $row;
        $list[$row['cnt_id']]['text'] = nl2br(htmlentities($row['cnt_text'], ENT_QUOTES, 'utf-8'));
    }
    $smarty->assign('counters', $list);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/counters.list.sm.html'));
} elseif ($_GET['cid'] == 'add') {//=============================================== ДОБАВИТЬ
    if (isset($_POST['to_save'])) {
        $cnt_title = cut_trash_string($_POST['cnt_title']);
        $cnt_text = cut_trash_html($_POST['cnt_text']);
        $cnt_active = (int) $_POST['cnt_active'];
        $cnt_sort = (int) $_POST['cnt_sort'];

        $db->sql = "INSERT INTO $dbc
                    (cnt_title, cnt_text, cnt_active, cnt_sort, cnt_datefrom)
                    VALUES
                    ('$cnt_title', '$cnt_text', '$cnt_active', '$cnt_sort', now())";
        if ($db->exec()) {
            header('location: counters.php');
            exit();
        }
    }
    if (isset($_POST['to_ret'])) {
        header('location: counters.php');
        exit();
    }
    $row = array(
        'cnt_title' => '',
        'cnt_text' => '',
        'cnt_sort' => 10,
        'cnt_active' => 0,
    );
    $smarty->assign('add', true);
    $smarty->assign('cnt', $row);
    $smarty->assign('is_edit', false);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/counters.item.sm.html'));
} elseif (is_numeric($_GET['cid'])) {//============================================= РЕДАКТИРОВАТЬ
    $cid = (int) $_GET['cid'];

    if (isset($_POST['to_save'])) {
        $cnt_title = cut_trash_string($_POST['cnt_title']);
        $cnt_text = cut_trash_html($_POST['cnt_text']);
        $cnt_active = (int) $_POST['cnt_active'];
        $cnt_sort = (int) $_POST['cnt_sort'];

        $db->sql = "UPDATE $dbc
                    SET
                    cnt_title = '$cnt_title', cnt_text='$cnt_text',
                    cnt_active='$cnt_active', cnt_sort='$cnt_sort'
                    WHERE cnt_id = '$cid'";
        if ($db->exec()) {
            header('location: counters.php');
            exit();
        }
    }
    if (isset($_POST['to_ret'])) {
        header('location: counters.php');
        exit();
    }

    $db->sql = "SELECT dbc.*FROM $dbc dbc WHERE cnt_id='$cid'";
    $db->exec();
    $row = $db->fetch();

    $smarty->assign('cnt', $row);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/counters.item.sm.html'));
}

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
?>
