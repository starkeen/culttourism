<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', false);
$_timer_start_main = microtime(true);
header('Content-Type: text/html; charset=utf-8');
include(realpath(dirname(__FILE__) . '/../config/configuration.php'));
include(_DIR_ROOT . '/includes/debug.php');

include(_DIR_INCLUDES . '/class.Helper.php');
spl_autoload_register('Helper::autoloader');

$db = new MyDB(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_BASENAME, DB_PREFIX);
$smarty = new mySmarty();
$sp = new MSysProperties($db);
$cr = new MCron($db);

$cron = $db->getTableName('cron');

$global_cron_email = $sp->getByName('mail_report_cron');

//-- если больше двух часов работает скрипт - зарубить
$cr->killPhantoms();

//* * ********    В Ы Б О Р К А   С К Р И П Т О В     ********* */
$scripts = $cr->getPortion();
if (empty($scripts)) {
    exit();
}

$nologging_ids = array(2,);

foreach ($scripts as $job) {
    $script = $job['cr_script'];
    $script_id = $job['cr_id'];
    $period = $job['period'];

    if (!in_array($script_id, $nologging_ids)) {
        Logging::addHistory('cron', "Начала работу задача №$script_id ({$job['cr_title']})");
    }

    $cr->markWorkStart($script_id);

    $_timer_start_script = microtime(true);
    ob_start();
    include(_DIR_ROOT . "/cron/$script");
    $content = ob_get_contents();
    ob_end_clean();
    $exectime = substr((microtime(true) - $_timer_start_script), 0, 6); // время выполнения в секундах
    if (strlen($content) != 0) {
        $content .= "<hr>время: $exectime c.";

        Mailing::sendDirect($global_cron_email, 'Cron on ' . _URL_ROOT, $content, 'X-Mailru-Msgtype:cronreport');
    }
    $cr->markWorkFinish($script_id, $content, $exectime);

    if (!in_array($script_id, $nologging_ids) && $exectime >= 0.01) {
        Logging::addHistory('cron', "Отработала задача №$script_id  ({$job['cr_title']}), время $exectime с.", $content);
    }
}

$cr->optimize();

//echo '<hr>Общее время работы скриптов: ' . substr(microtime(true) - $_timer_start_main, 0, 6) . ' c.';