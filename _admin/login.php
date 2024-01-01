<?php

require_once '_common.php';

$smarty->assign('title', 'Авторизация в системе');
$login = '';
$error = '';
if (isset($_GET['out'])) {
    $ticket->deleteKey();
    unset(
        $_SESSION['auth'],
        $_SESSION['user_auth'],
        $_SESSION['user_name'],
        $_SESSION['user_admin'],
        $_SESSION['user_id']
    );
    header('Location: login.php');
    exit();
}

if (isset($_POST) && !empty($_POST)) {
    $login = trim($_POST['login']);
    $pwd = trim($_POST['pass']);
    $retpath = !empty($_GET['r']) ? urldecode(trim($_GET['r'])) : 'index.php';

    if ($ticket->checkLoginPassword($login, $pwd)) {
        $_SESSION['auth'] = $ticket->key;
        header("Location: $retpath");
        exit();
    }

    $error = 'Авторизация не удалась. Попробуйте еще раз';
}

$smarty->assign('login', $login);
$smarty->assign('error', $error);
$smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/login.tpl'));

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');

exit();
