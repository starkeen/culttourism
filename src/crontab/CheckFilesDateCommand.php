<?php

declare(strict_types=1);

namespace app\crontab;

use Mailing;
use MSysProperties;

/**
 * Проверка дат последнего изменения файлов
 */
class CheckFilesDateCommand extends AbstractCrontabCommand
{
    private const DIRS = [
        '_admin',
        '_utils',
        'addons',
        'config',
        'cron',
        'data',
        'img',
        'includes',
        'models',
        'js',
        'css',
        'modules',
        'templates',
    ];

    private MSysProperties $systemPropertiesModel;

    public function __construct(MSysProperties $sysProperties)
    {
        $this->systemPropertiesModel = $sysProperties;
    }

    public function run(): void
    {
        clearstatcache(true);

        $files = [];
        $filesSkip = [];
        $timestampMax = 0;
        $filenameLast = '';

        $skipDirs = [
            GLOBAL_DIR_ROOT . '/data/logs',
            GLOBAL_DIR_ROOT . '/data/feed',
        ];

        $files[] = GLOBAL_DIR_ROOT . '/index.php';
        $files[] = GLOBAL_DIR_ROOT . '/robots.txt';
        $files[] = GLOBAL_DIR_ROOT . '/.htaccess';

        foreach (self::DIRS as $dir) {
            foreach (glob(GLOBAL_DIR_ROOT . "/$dir/*.*") as $filename) {
                $files[] = $filename;
            }
            foreach (glob(GLOBAL_DIR_ROOT . "/$dir/*/*.*") as $filename) {
                $files[] = $filename;
            }
            foreach (glob(GLOBAL_DIR_ROOT . "/$dir/*/*/*.*") as $filename) {
                $files[] = $filename;
            }
        }

        foreach ($skipDirs as $dir) {
            foreach (glob("$dir/*.*") as $filename) {
                $filesSkip[] = $filename;
            }
            foreach (glob("$dir/*/*.*") as $filename) {
                $filesSkip[] = $filename;
            }
            foreach (glob("$dir/*/*/*.*") as $filename) {
                $filesSkip[] = $filename;
            }
        }

        foreach ($filesSkip as $filename) {
            $idx = array_search($filename, $files, true);
            if ($idx > 0) {
                unset($files[$idx]);
            }
        }

        foreach ($files as $filename) {
            $timestamp = filemtime($filename);
            if ($timestamp > $timestampMax) {
                $timestampMax = $timestamp;
                $filenameLast = $filename;
            }
        }

        $lastUpdate = (int) $this->systemPropertiesModel->getByName('site_lastupdate');

        $this->systemPropertiesModel->updateByName('site_lastupdate', $timestampMax);
        $this->systemPropertiesModel->updateByName('site_version', date('Ymd-Hi', $timestampMax));

        if ($lastUpdate !== $timestampMax) {
            //тревожное письмо
            $mailAttrs = [
                'datetime_max' => date('d.m.Y H:i:s', $timestampMax),
                'datetime_last' => date('d.m.Y H:i:s', $lastUpdate),
                'filename_last' => $filenameLast,
            ];
            $reportingEmail = $this->systemPropertiesModel->getByName('mail_report_cron');
            Mailing::sendLetterCommon($reportingEmail, 3, $mailAttrs);
        }
    }
}
