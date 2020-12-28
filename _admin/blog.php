<?php

use JetBrains\PhpStorm\NoReturn;

require_once('common.php');

$smarty->assign('title', 'Управление записями в блоге');

$dbb = $db->getTableName('blogentries');
$dbu = $db->getTableName('users');

if (!isset($_GET['id']) && !isset($_GET['act'])) {
    $bloglist = [];
    $db->sql = "SELECT bg.br_id, bg.br_title, us.us_name, br_active,
                IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link,
                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex
                FROM $dbb bg
                LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                ORDER BY bg.br_date DESC";
    $db->exec();
    while ($row = $db->fetch()) {
        $bloglist[$row['br_id']] = $row;
    }
    $smarty->assign('bloglist', $bloglist);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/blog/admin.all.tpl'));
} elseif (isset($_GET['id']) && !isset($_GET['act'])) {
    $id = (int) $_GET['id'];
    if (isset($_POST) && !empty($_POST)) {
        if (isset($_POST['to_save'])) {
            $br_title = trim($_POST['br_title']);
            $br_url = trim($_POST['br_url']);
            $br_text = trim($_POST['br_text']);
            $br_active = (int) $_POST['br_active'];
            $db->sql = "UPDATE $dbb SET
                        br_title = '$br_title', br_text = '$br_text', br_url = '$br_url', br_active = '$br_active'
                        WHERE br_id = '$id'";
            if ($db->exec()) {
                redirectBlog();
            }
        } elseif (isset($_POST['to_ret'])) {
            redirectBlog();
        }
        if (isset($_POST['to_del'])) {
            $db->sql = "DELETE FROM $dbb WHERE br_id = '$id'";
            if ($db->exec()) {
                redirectBlog();
            }
        }
    }
    $db->sql = "SELECT bg.br_id, bg.br_title, us.us_name, br_active, br_text, br_url,
                IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link,
                DATE_FORMAT(bg.br_date,'%Y/%m') as bg_datelink,
                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex
                FROM $dbb bg
                LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                WHERE bg.br_id = '$id'";
    $db->exec();
    $record = $db->fetch();
    $smarty->assign('blogitem', $record);
    $smarty->assign('is_edit', true);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/blog/admin.one.tpl'));
} elseif (!isset($_GET['id']) && isset($_GET['act'])) {
    if (isset($_POST) && !empty($_POST)) {
        if (isset($_POST['to_save'])) {
            $br_title = trim($_POST['br_title']);
            $br_url = trim($_POST['br_url']);
            $br_text = trim($_POST['br_text']);
            $br_active = (int) $_POST['br_active'];
            $br_us_id = (int) $_SESSION['user_id'];
            $db->sql = "INSERT INTO $dbb SET
                        br_title = '$br_title', br_text = '$br_text', br_url = '$br_url', br_active = '$br_active', br_us_id = '$br_us_id', br_date = now()";
            if ($db->exec()) {
                redirectBlog();
            }
        } elseif (isset($_POST['to_ret'])) {
            redirectBlog();
        }
    }
    $record = [];
    $record['br_title'] = '';
    $record['br_url'] = '';
    $record['br_active'] = 0;
    $record['bg_datelink'] = date('Y') . '/' . date('m');
    $smarty->assign('blogitem', $record);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/blog/admin.one.tpl'));
}
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();

/**
 *
 */
function redirectBlog(): void
{
    header('location:blog.php');
    exit;
}
