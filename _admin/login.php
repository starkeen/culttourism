<?php

require_once('common.php');

$smarty->assign('title', 'Авторизация в системе');
$login = '';
$error = '';
if (isset($_GET['out'])) {
    $ticket->deleteKey();
    unset($_SESSION['auth']);
    unset($_SESSION['user_auth']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_admin']);
    unset($_SESSION['user_id']);
    header('Location: login.php');
    exit();
}

if (isset($_POST) && !empty($_POST)) {
    $login = cut_trash_word($_POST['login']);
    $pwd = cut_trash_text($_POST['pass']);
    $retpath = (isset($_GET['r']) && $_GET['r'] != '') ? urldecode(cut_trash_text($_GET['r'])) : 'index.php';

    if ($ticket->checkPassword($login, $pwd)) {
        $_SESSION['auth'] = $ticket->key;
        header("Location: $retpath");
        exit();
    } else {
        $error = 'Авторизация не удалась. Попробуйте еще раз';
    }
}

$smarty->assign('login', $login);
$smarty->assign('error', $error);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/login.sm.html'));
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
?>