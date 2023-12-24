<?php

require_once 'common.php';

$smarty->assign('title', 'Управление доступом пользователей к сайту');

$dbu = $db->getTableName('users');
$error = null;

if (!isset($_GET['user_id']) && !isset($_GET['act'])) {
    $db->sql = "SELECT * FROM $dbu";
    $db->exec();
    while ($row = $db->fetch()) {
        $userlist[$row['us_id']] = $row;
    }
    $smarty->assign('userlist', $userlist);
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/users.list.tpl'));
} elseif (isset($_GET['user_id']) && !isset($_GET['act'])) {
    $us_id = (int) $_GET['user_id'];
    if (isset($_POST['to_save'])) {
        $us_name = trim($_POST['us_name']);
        $us_login = trim($_POST['us_login']);
        $us_pass1 = trim($_POST['us_pass1']);
        $us_pass2 = trim($_POST['us_pass2']);
        $us_email = trim($_POST['us_email']);
        $us_male = (int) $_POST['us_male'];
        $us_admin = (isset($_POST['us_admin'])) ? (int) $_POST['us_admin'] : null;
        $us_active = (isset($_POST['us_active'])) ? (int) $_POST['us_active'] : null;
        $db->sql = "UPDATE $dbu
                    SET us_name = '$us_name',
                    us_login = '$us_login',
                    us_email = '$us_email',
                    us_male = '$us_male'";
        if ($us_pass1 === $us_pass2 && strlen($us_pass1) !== 0) {
            $db->sql .= ",  us_passwrd = MD5('$us_pass1')";
        }
        if ($us_admin !== null) {
            $db->sql .= ",  us_admin = '$us_admin'";
        }
        if ($us_active !== null) {
            $db->sql .= ",  us_active = '$us_active'";
        }
        $db->sql .= "\nWHERE us_id = '$us_id'";
        if ($db->exec()) {
            redirect();
        }
    }
    if (isset($_POST['to_ret'])) {
        redirect();
    }
    if (isset($_POST['to_del'])) {
        $db->sql = "DELETE FROM $dbu WHERE us_id = '$us_id'";
        if ($db->exec()) {
            redirect();
        }
    }

    $db->sql = "SELECT * FROM $dbu WHERE us_id='$us_id'";
    $db->exec();
    $row = $db->fetch();
    $smarty->assign('user', $row);
    $smarty->assign('is_admin', true);
    $smarty->assign('is_edit', true);
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/users.item.tpl'));
} elseif (!isset($_GET['user_id']) && isset($_GET['act']) && $_GET['act'] === 'add') {
    if (isset($_POST['to_save'])) {
        $us_name = trim($_POST['us_name']);
        $user['us_name'] = $us_name;
        $us_login = trim($_POST['us_login']);
        $user['us_login'] = $us_login;
        $us_pass1 = trim($_POST['us_pass1']);
        $us_pass2 = trim($_POST['us_pass2']);
        $us_email = trim($_POST['us_email']);
        $user['us_email'] = $us_email;
        if (isset($_POST['us_male'])) {
            $us_male = (int) $_POST['us_male'];
            $user['us_male'] = $us_male;
        } else {
            $us_male = null;
        }
        $us_admin = (int) $_POST['us_admin'];
        if ($us_pass1 !== $us_pass2) {
            $error = 'Введенные пароли не совпадают!';
        } elseif (strlen($us_name) === 0) {
            $error = 'Вы не указали имя пользователя!';
        } elseif (strlen($us_login) === 0) {
            $error = 'Вы не указали логин!';
        } elseif (strlen($us_pass1) < 5) {
            $error = 'Минимальная длина пароля 5 символов!';
        } elseif ($us_male === null) {
            $error = 'Вы не указали пол';
        } else {
            $db->sql = "INSERT INTO $dbu (us_name, us_login, us_passwrd, us_email, us_male, us_admin, us_active)
                        VALUES ('$us_name', '$us_login', MD5('$us_pass1'), '$us_email', '$us_male', '$us_admin', '1')";
            if ($db->exec()) {
                redirect();
            }
        }
    }
    $smarty->assign('user', ['us_name' => '', 'us_login' => '', 'us_email' => '', 'us_admin' => '']);
    $smarty->assign('is_edit', false);
    $smarty->assign('is_admin', true);
    $smarty->assign('is_error', $error);
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/users.item.tpl'));
}


$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();

function redirect(): void
{
    header('location:users.php');
    exit;
}
