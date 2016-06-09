<?php

require_once('common.php');

$smarty->assign('title', 'Фотографии');

if (isset($_GET['act'])) {
    if (!empty($_FILES)) {
        $file = $_FILES['photo'];
    }
    header('Location: photos.php');
    exit;
} else {
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/photos.list.sm.html'));
    $smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
    exit();
}