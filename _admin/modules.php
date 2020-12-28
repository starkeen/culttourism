<?php

include 'common.php';

$smarty->assign('title', 'Модули и страницы на сайте');

$md_id = null;

$dbm = $db->getTableName('modules');
$dbp = $db->getTableName('pages');

if (isset($_GET['id']) && strlen($_GET['id']) != 0) {
    $md_id = (int) $_GET['id'];

    if (isset($_POST['sender'])) {
        //================================ SAVE DATA ===========================
        if (!isset($_POST['md_pid'])) {
            $_POST['md_pid'] = null;
        }
        if (!isset($_POST['md_name'])) {
            $_POST['md_name'] = '[не указано]';
        }
        $md_title = trim($_POST['md_title']);
        $md_name = trim($_POST['md_name']);
        $md_pid = (int) $_POST['md_pid'];
        $md_url = trim($_POST['md_url']);
        $md_redirect_flg = (int) $_POST['md_redirect_flg'];
        $md_redirect = trim($_POST['md_redirect']);
        $md_keywords = trim($_POST['md_keywords']);
        $md_description = trim($_POST['md_description']);
        $md_pagecontent = isset($_POST['md_pagecontent']) ? trim($_POST['md_pagecontent']) : '';
        $md_active = (int) $_POST['md_active'];
        $md_css = (int) $_POST['md_css'];
        $md_robots = trim($_POST['md_robots']);
        $md_sort = (int) $_POST['md_sort'];

        if ($md_redirect_flg !== 0 && strlen($md_redirect) !== 0) {
            $redir = "'$md_redirect'";
        } else {
            $redir = 'null';
        }
        $replace_list = ['/', '\\', ' ', '?', '&'];
        $md_url = str_replace($replace_list, '', $md_url);

        if ($_POST['actiontype'] !== 'add') {
            $sql = "UPDATE $dbm
                    SET md_title='$md_title', md_pagecontent = '$md_pagecontent',
                    md_url = '$md_url', md_redirect = $redir,
                    md_description = '$md_description', md_keywords = '$md_keywords',
                    md_active = '$md_active', md_css='$md_css',
                    md_robots='$md_robots', md_sort='$md_sort'
                    WHERE md_id = '$md_id'";
            $db->exec($sql);
        } else {
            $sql = "INSERT INTO $dbm
                    SET md_name = '$md_name', md_pid='$md_pid', md_title='$md_title',
                    md_pagecontent = '$md_pagecontent',
                    md_url = '$md_url', md_redirect = $redir,
                    md_description = '$md_description', md_keywords = '$md_keywords',
                    md_active = '$md_active', md_css='$md_css',
                    md_robots='$md_robots', md_sort='$md_sort'";
            $db->exec($sql);
            $newmd = $db->getLastInserted();
            header('location:modules.php?id=' . $newmd);
            exit();
        }
    }

    //============================== / SAVE DATA ===========================

    if ($md_id != 'add') {
        $db->sql = "SELECT * FROM $dbm WHERE md_id = '$md_id' LIMIT 1";
        $res = $db->exec();
        $row = $db->fetch();
    } else {
        $row = [];
        $row['md_name'] = 'новая страница';
        $row['md_url'] = '';
        $row['md_title'] = '';
        $row['md_pid'] = null;
        $row['md_keywords'] = '';
        $row['md_description'] = '';
        $row['md_active'] = 1;
        $row['md_css'] = 0;
        $row['md_robots'] = 'index, follow';
        $row['md_sort'] = '0';
        $row['md_redirect'] = '';
    }

    $row['md_name'] = htmlentities($row['md_name'], ENT_QUOTES, 'utf-8');
    if (!isset($row['md_url'])) {
        $row['md_url'] = null;
    }
    $mod_url = $row['md_url'];

    if (isset($row['md_pid'])) {
        $db->sql = "SELECT md_url, md_name FROM $dbm WHERE md_id = '{$row['md_pid']}' LIMIT 1";
        $db->exec();
        $rowparent = $db->fetch();
        $row['parent'] = $rowparent;
    }
    $smarty->assign('mod_item', $row);
    $smarty->assign('site_url', _SITE_URL);

    $text_edit = true;

    $subpages = [];
    if ($md_id != 0) {
        $smarty->assign('mod_id', $md_id);
        $db->sql = "SELECT * FROM $dbp WHERE pg_md_id = '$md_id'";
        $db->exec();
        $subpages = $db->fetchAll();
    } else {
        $smarty->assign('mod_id', 'add');
    }
    $smarty->assign('text_edit', $text_edit);
    $smarty->assign('subpages', $subpages);
} else {
    $smarty->assign('mod_id', null);
}
//* * ************************************************************************************************ */
$db->sql = "SELECT *
            FROM $dbm
            ORDER BY md_pid, md_sort";
$db->exec();
$modules = [];
while ($row = $db->fetch()) {
    if ($row['md_pid'] != 0) {
        $modules[$row['md_pid']]['md_tree'][$row['md_id']] = $row;
    } else {
        $modules[$row['md_id']] = $row;
        $modules[$row['md_id']]['md_tree'] = null;
    }
}
//print_x($modules);
$smarty->assign('mod_list', $modules);
$smarty->assign('is_admin', $isAdmin);

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/modules.tpl'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
