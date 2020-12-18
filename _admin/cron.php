<?php

require('common.php');
$smarty->assign('title', 'Задачи по расписанию');
$dbcron = $db->getTableName('cron');

if (!isset($_GET['crid']) && !isset($_GET['act'])) {
    //-------------------------------------------------------------------------
    $db->sql = "SELECT cr_id, cr_title, cr_script, cr_active, cr_period, cr_isrun, cr_lastexectime, cr_lastresult,
                DATE_FORMAT(cr_datelast, '%d.%m.%Y %H:%i:%s') as date_lastrun,
                DATE_FORMAT(cr_datelast_attempt, '%d.%m.%Y %H:%i:%s') as date_lastatt,
                DATE_FORMAT(cr_datenext, '%d.%m.%Y %H:%i:%s') as date_next
                FROM $dbcron";
    $db->exec();
    $crons = array();
    while ($row = $db->fetch()) {
        $crons[] = $row;
    }
    $smarty->assign('crons', $crons);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/cron.list.sm.html'));
}
//-------------------------------------------------------------------------
elseif (isset($_GET['crid']) && isset($_GET['act']) && $_GET['act'] == 'edit') {
    $crid = (int) $_GET['crid'];

    if (isset($_POST) && !empty($_POST)) {
        $ch_act = (int) $_POST['ch_act'];
        $ch_run = (int) $_POST['ch_run'];
        $period = cut_trash_string($_POST['period']);
        $next_time = cut_trash_string($_POST['next_time']);
        $next_day = cut_trash_string($_POST['next_day']);
        $db->sql = "UPDATE $dbcron SET
                    cr_active='$ch_act', cr_period='$period', cr_isrun='$ch_run',
                    cr_datenext='$next_day $next_time'
                    WHERE cr_id = '$crid'";
        if ($db->exec()) {
            header("Location: cron.php");
            exit();
        }
    }

    $db->sql = "SELECT cr_id, cr_title, cr_script, cr_active, cr_period, cr_isrun, cr_lastexectime, cr_lastresult,
                DATE_FORMAT(cr_datelast, '%d.%m.%Y %H:%i:%s') as date_lastrun,
                DATE_FORMAT(cr_datelast_attempt, '%d.%m.%Y %H:%i:%s') as date_lastatt,
                DATE_FORMAT(cr_datenext, '%Y-%m-%d') as day_next,
                DATE_FORMAT(cr_datenext, '%H:%i:%s') as time_next
                FROM $dbcron
                WHERE cr_id = '$crid'";
    $db->exec();
    $row = $db->fetch();
    $smarty->assign('task', $row);
    $smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/cron.item.sm.html'));
}
//-------------------------------------------------------------------------
elseif (isset($_GET['crid']) && isset($_GET['act']) && $_GET['act'] == 'run') {
    $crid = (int) $_GET['crid'];
    $db->sql = "SELECT cr_id, cr_script, DATE_FORMAT(cr_period, '%d %H:%i') as period FROM $dbcron WHERE cr_id = '$crid'";
    if ($db->exec()) {
        $job = $db->fetch();
        $script = $job['cr_script'];
        $script_id = $job['cr_id'];
        $period = $job['period'];

        $db->exec("UPDATE $dbcron SET cr_isrun = '1', cr_datelast_attempt = now() WHERE cr_id = $script_id");

        $_timer_start_script = microtime(true);
        ob_start();
        include(_DIR_ROOT . "/cron/$script");
        $content = ob_get_contents();
        ob_end_clean();
        $exectime = substr((microtime(true) - $_timer_start_script), 0, 6); // время выполнения в секундах
        if (strlen($content) != 0) {
            $content .= "<hr>время: $exectime c.";
        }
        echo $content;
        $db->exec("UPDATE $dbcron SET
                    cr_isrun = '0',
                    cr_lastexectime = '$exectime',
                    cr_lastresult = '$content',
                    cr_datenext = DATE_ADD(cr_datenext, INTERVAL '$period' DAY_MINUTE),
                    cr_datelast = now()
                    WHERE cr_id = '$script_id'");
    }
    header("Location: cron.php");
    exit();
}
//-------------------------------------------------------------------------
elseif (isset($_GET['crid']) && isset($_GET['act']) && $_GET['act'] === 'stop') {
    $crid = (int) $_GET['crid'];
    $db->sql = "UPDATE $dbcron SET
                cr_active='0', cr_isrun='0'
                WHERE cr_id = '$crid'";
    if ($db->exec()) {
        header("Location: cron.php");
        exit();
    } else {
        header("Location: cron.php?crid=$crid&act=edit");
        exit();
    }
} else {
    die('error');
}
$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
