<?php

require_once('common.php');
include(_DIR_INCLUDES . '/class.FCKeditor.php');

$smarty->assign('title', 'Модули и страницы на сайте');

$md_id = null;

$dbm = $db->getTableName('modules');

if (isset($_GET['id']) && strlen($_GET['id']) != 0) {
    $md_id = intval($_GET['id']);

    if (isset($_POST['sender'])) {
        //================================ SAVE DATA ===========================
        if (!isset($_POST['md_pid']))
            $_POST['md_pid'] = null;
        if (!isset($_POST['md_name']))
            $_POST['md_name'] = null;
        $md_title = cut_trash_string($_POST['md_title']);
        $md_name = cut_trash_string($_POST['md_name']);
        $md_pid = cut_trash_int($_POST['md_pid']);
        $md_url = cut_trash_string($_POST['md_url']);
        $md_redirect_flg = cut_trash_int($_POST['md_redirect_flg']);
        $md_redirect = cut_trash_string($_POST['md_redirect']);
        $md_keywords = cut_trash_string($_POST['md_keywords']);
        $md_description = cut_trash_string($_POST['md_description']);
        $md_pagecontent = cut_trash_html($_POST['md_pagecontent']);
        $md_active = cut_trash_int($_POST['md_active']);
        $md_counters = cut_trash_int($_POST['md_counters']);
        $md_css = cut_trash_int($_POST['md_css']);
        $md_robots = cut_trash_string($_POST['md_robots']);
        $md_sort = cut_trash_int($_POST['md_sort']);

        if ($md_redirect_flg !== 0 && strlen($md_redirect) != 0)
            $redir = "'$md_redirect'";
        else
            $redir = 'null';
        $replace_list = array('/', '\\', ' ', '?', '&');
        $md_url = str_replace($replace_list, '', $md_url);

        if ($_POST['actiontype'] != 'add') {
            $sql = "UPDATE $dbm
                    SET md_title='$md_title', md_pagecontent = '$md_pagecontent',
                    md_url = '$md_url', md_redirect = $redir,
                    md_description = '$md_description', md_keywords = '$md_keywords',
                    md_active = '$md_active', md_counters = '$md_counters', md_css='$md_css',
                    md_robots='$md_robots', md_sort='$md_sort'
                    WHERE md_id = '$md_id'";
            $db->exec($sql);
        } else {
            $sql = "INSERT INTO $dbm
                    SET md_name = '$md_name', md_pid='$md_pid', md_title='$md_title',
                    md_pagecontent = '$md_pagecontent',
                    md_url = '$md_url', md_redirect = $redir,
                    md_description = '$md_description', md_keywords = '$md_keywords',
                    md_active = '$md_active', md_counters = '$md_counters', md_css='$md_css',
                    md_robots='$md_robots', md_sort='$md_sort'";
            $db->exec($sql);
            $newmd = $db->getLastInserted();
            header('location:modules.php?id=' . $newmd);
        }
    }

    //============================== / SAVE DATA ===========================

    if ($md_id != 'add') {
        $sql = "SELECT * FROM $dbm WHERE md_id = '$md_id' LIMIT 1";
        $res = $db->exec($sql);
        $row = mysql_fetch_assoc($res);
    } else {
        $row = array();
        $row['md_name'] = 'новая страница';
        $row['md_url'] = '';
        $row['md_title'] = '';
        $row['md_pid'] = null;
        $row['md_keywords'] = '';
        $row['md_description'] = '';
        $row['md_active'] = 1;
        $row['md_counters'] = 1;
        $row['md_css'] = 0;
        $row['md_robots'] = 'index, follow';
    }

    $row['md_name'] = htmlentities($row['md_name'], ENT_QUOTES, 'utf-8');
    if (!isset($row['md_url']))
        $row['md_url'] = null;
    $mod_url = $row['md_url'];

    if (isset($row['md_pid'])) {
        $sql = "SELECT md_url, md_name FROM $dbm WHERE md_id = '{$row['md_pid']}' LIMIT 1";
        $res = $db->exec($sql);
        $rowparent = mysql_fetch_assoc($res);
        $row['parent'] = $rowparent;
    }
    $smarty->assign('mod_item', $row);
    $smarty->assign('site_url', _SITE_URL);

    if (!file_exists(_DIR_MODULES . '/' . $row['md_url'] . '/admin.php')) {
        $oFCKeditor = new MyFCK('md_pagecontent');
        $oFCKeditor->Height = 400;
        if (!isset($row['md_pagecontent']))
            $row['md_pagecontent'] = '';
        $oFCKeditor->Value = $row['md_pagecontent'];
        $pagetext = $oFCKeditor->CreateHtml();
    } else {
        include(_DIR_MODULES . '/' . $row['md_url'] . '/admin.php');
        $pagetext = getAdminArea($smarty);
    }
    $smarty->assign('FCK_pagetext', $pagetext);
    if ($md_id != 0)
        $smarty->assign('mod_id', $md_id);
    else
        $smarty->assign('mod_id', 'add');
}
else
    $smarty->assign('mod_id', null);
/* * ************************************************************************************************ */
$sql = "SELECT md_id, md_pid, md_name, md_active, md_redirect, md_css
        FROM $dbm
        ORDER BY md_pid, md_sort";
$res = $db->exec($sql);
$modules = array();
while ($row = mysql_fetch_assoc($res)) {
    if ($row['md_pid'] != 0)
        $modules[$row['md_pid']]['md_tree'][$row['md_id']] = $row;
    else {
        $modules[$row['md_id']] = $row;
        $modules[$row['md_id']]['md_tree'] = NULL;
    }
}
//print_x($modules);
$smarty->assign('mod_list', $modules);
$smarty->assign('is_admin', $isAdmin);

$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/modules.sm.html'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
?>