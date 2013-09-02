<?php

$_timer_start_main = microtime(true);
header('Content-Type: text/html; charset=utf-8');
include(realpath(dirname(__FILE__) . '/../config/configuration.php'));
include(_DIR_ROOT . '/includes/class.myDB.php');
include(_DIR_ROOT . '/includes/class.mySmarty.php');
include(_DIR_ROOT . '/includes/class.Logging.php');
include(_DIR_ROOT . '/includes/debug.php');

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);
$smarty = new mySmarty();

$cron = $db->getTableName('cron');

//-- если больше двух часов работает скрипт - зарубить
$db->exec("UPDATE $cron SET cr_isrun = 0
           WHERE cr_isrun=1 AND cr_active=1
           AND TIMEDIFF(now(),cr_datelast_attempt) > '02:00:00'");

//* * ********    В Ы Б О Р К А   С К Р И П Т О В     ********* */
$db->sql = "SELECT *, DATE_FORMAT(cr_period, '%d %H:%i') as period FROM $cron
                WHERE cr_active = 1 AND cr_isrun = 0 AND cr_datenext <= now()";
$db->exec();
while ($row = $db->fetch()) {
    $scripts[$row['cr_id']] = $row;
}
if (!isset($scripts)) {
    //echo 'Nothing to do. [' . date('d.m.Y H:i:s') . ']';
    exit();
}

$nologging_ids = array(2,);

foreach ($scripts as $job) {
    $script = $job['cr_script'];
    $script_id = $job['cr_id'];
    $period = $job['period'];

    if (!in_array($script_id, $nologging_ids))
        Logging::addHistory('cron', "Начала работу задача №$script_id ({$job['cr_title']})");

    $db->exec("UPDATE $cron SET cr_isrun = '1', cr_datelast_attempt = now() WHERE cr_id = $script_id");

    $_timer_start_script = microtime(true);
    ob_start();
    include(_DIR_ROOT . "/cron/$script");
    $content = ob_get_contents();
    ob_end_clean();
    $exectime = substr((microtime(true) - $_timer_start_script), 0, 6); // время выполнения в секундах
    if (strlen($content) != 0)
        $content .= "<hr>время: $exectime c.";
    echo $content;
    $db->exec("UPDATE $cron SET
                    cr_isrun = '0',
                    cr_lastexectime = '$exectime',
                    cr_lastresult = '$content',
                    cr_datenext = DATE_ADD(cr_datenext, INTERVAL '$period' DAY_MINUTE),
                    cr_datelast = now()
                    WHERE cr_id = $script_id");

    if (!in_array($script_id, $nologging_ids) && $exectime >= 0.01)
        Logging::addHistory('cron', "Отработала задача №$script_id  ({$job['cr_title']}), время $exectime с.", $content);
}

//-- поправить ключи
$db->exec("OPTIMIZE TABLE $cron");

//echo '<hr>Общее время работы скриптов: ' . substr(microtime(true) - $_timer_start_main, 0, 6) . ' c.';
?>
