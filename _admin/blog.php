<?php
require_once('common.php');

$smarty->assign('title', 'Управление записями в блоге');

$dbb = $db->getTableName('blogentries');
$dbu = $db->getTableName('users');

if (!isset($_GET['id']) && !isset($_GET['act'])) {
    $bloglist = array();
    $db->sql = "SELECT bg.br_id, bg.br_title, us.us_name, br_active,
                IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link,
                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex
                FROM $dbb bg
                LEFT JOIN $dbu us ON bg.br_us_id = us.us_id
                ORDER BY bg.br_date DESC";
    //$db->showSQL();
    $db->exec();
    while($row = $db->fetch()) {
        $bloglist[$row['br_id']] = $row;
    }
    $smarty->assign('bloglist', $bloglist);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES.'/blog/admin.all.sm.html'));
}
elseif(isset($_GET['id']) && !isset($_GET['act'])) {
    include(_DIR_INCLUDES.'/class.FCKeditor.php');
    $id = intval($_GET['id']);
    if (isset($_POST) && !empty($_POST)) {
        if (isset($_POST['to_save'])) {
            $br_title = cut_trash_string($_POST['br_title']);
            $br_url = cut_trash_string($_POST['br_url']);
            $br_text = cut_trash_html($_POST['br_text']);
            $br_active = cut_trash_int($_POST['br_active']);
            $db->sql = "UPDATE $dbb SET
                        br_title = '$br_title', br_text = '$br_text', br_url = '$br_url', br_active = '$br_active'
                        WHERE br_id = '$id'";
            if ($db->exec()) header('location:blog.php');
        }
        elseif(isset($_POST['to_ret'])) header('location:blog.php');
        if(isset($_POST['to_del'])) {
            $db->sql = "DELETE FROM $dbb WHERE br_id = '$id'";
            if ($db->exec()) header('location:blog.php');
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
    $oFCKeditor = new MyFCK('br_text');
    $oFCKeditor->Height = 400;
    $oFCKeditor->Value = $record['br_text'];
    $record['br_texteditor'] = $oFCKeditor->CreateHtml();
    $smarty->assign('blogitem', $record);
    $smarty->assign('is_edit', TRUE);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES.'/blog/admin.one.sm.html'));
}
elseif(!isset($_GET['id']) && isset($_GET['act'])) {
    include(_DIR_INCLUDES.'/class.FCKeditor.php');

    if (isset($_POST) && !empty($_POST)) {
        if (isset($_POST['to_save'])) {
            $br_title = cut_trash_string($_POST['br_title']);
            $br_url = cut_trash_string($_POST['br_url']);
            $br_text = cut_trash_html($_POST['br_text']);
            $br_active = cut_trash_int($_POST['br_active']);
            $br_us_id = cut_trash_int($_SESSION['user_id']);
            $db->sql = "INSERT INTO $dbb SET
                        br_title = '$br_title', br_text = '$br_text', br_url = '$br_url', br_active = '$br_active', br_us_id = '$br_us_id', br_date = now()";
            if ($db->exec()) header('location:blog.php');
        }
        elseif(isset($_POST['to_ret'])) header('location:blog.php');
    }
    $record = array();
    $record['br_title'] = '';
    $record['br_url'] = '';
    $record['br_active'] = 0;
    $record['bg_datelink'] = date('Y').'/'.date('m');
    $oFCKeditor = new MyFCK('br_text');
    $oFCKeditor->Height = 400;
    $oFCKeditor->Value = '';
    $record['br_texteditor'] = $oFCKeditor->CreateHtml();
    $smarty->assign('blogitem', $record);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES.'/blog/admin.one.sm.html'));
}
$smarty->display(_DIR_TEMPLATES.'/_admin/admpage.sm.html');
exit();
?>