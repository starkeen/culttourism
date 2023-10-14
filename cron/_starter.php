<?php

use app\core\application\CrontabApplication;

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', false);
$_timer_start_main = microtime(true);
header('Content-Type: text/html; charset=utf-8');
include(dirname(__DIR__) . '/config/configuration.php');
include __DIR__ . '/../vendor/autoload.php';
$app = new CrontabApplication();
$app->run();

$logger = $app->getLogger();

$db = $app->getDb();
$smarty = $app->getTemplateEngine();
$sp = new MSysProperties($db);
$cr = new MCron($db);

$globalCronEmail = $sp->getByName('mail_report_cron');

//-- если больше двух часов работает скрипт - зарубить
$cr->killPhantoms();

//* * ********    В Ы Б О Р К А   С К Р И П Т О В     ********* */
$scripts = $cr->getPortion();
if (empty($scripts)) {
    exit();
}

$nologgingIDs = [2,];

$execTime = 0;
$content = null;

foreach ($scripts as $job) {
    $script = $job['cr_script'];
    $scriptId = (int) $job['cr_id'];
    $monitorId = $job['monitor_id'] ?: null;
    $logContext = [
        'id' => $scriptId,
        'title' => $job['cr_title'],
        'content' => null,
        'timing' => null,
    ];

    try {
        if (!in_array($scriptId, $nologgingIDs, true)) {
            $logger->debug('Начало работы задачи crontab', $logContext);
        }

        $logger->cronMonitorRun($monitorId);

        $cr->markWorkStart($scriptId);

        $_timer_start_script = microtime(true);
        ob_start();
        include(GLOBAL_DIR_ROOT . "/cron/$script");
        $content = ob_get_contents();
        ob_end_clean();
        $execTimeMs = (microtime(true) - $_timer_start_script) * 1000;
        $execTime = substr($execTimeMs / 1000, 0, 6); // время выполнения в секундах
        if (strlen($content) !== 0) {
            $content .= "<hr>время: $execTime c.";
            Mailing::sendDirect(
                $globalCronEmail,
                'Cron on ' . GLOBAL_URL_ROOT,
                $content,
                'X-Mailru-Msgtype: cronreport'
            );
        }
        $cr->markWorkFinish($scriptId, $content, $execTime);

        $logger->cronMonitorDone($monitorId, (int) $execTimeMs);
    } catch (Throwable $exception) {
        $logger->sendSentryException($exception);
        $logger->cronMonitorFail($monitorId);
    }

    if ($execTime >= 0.01 && !in_array($scriptId, $nologgingIDs, true)) {
        $logContext['content'] = $content;
        $logContext['timing'] = $execTime;
        $logger->debug('Окончание работы задачи crontab', $logContext);
    }
}
