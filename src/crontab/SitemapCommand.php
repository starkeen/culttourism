<?php

declare(strict_types=1);

namespace app\crontab;

use app\db\MyDB;
use app\exceptions\CrontabException;
use app\sys\TemplateEngine;

class SitemapCommand extends AbstractCrontabCommand
{
    private MyDB $db;

    private TemplateEngine $smarty;

    public function __construct(MyDB $db, TemplateEngine $smarty)
    {
        $this->db = $db;
        $this->smarty = $smarty;
    }

    /**
     * @throws CrontabException
     */
    public function run(): void
    {
        $filename = GLOBAL_DIR_ROOT . '/sitemap.xml'; //имя sitemap-файла

        $filesizeOld = filesize($filename);

        $baseUrl = 'https://' . GLOBAL_URL_ROOT;

        $urls = [];

        $this->db->sql = "SELECT md_url, DATE_FORMAT(md_lastedit, '%Y-%m-%dT%H:%i:%s+00:00') date_update
                          FROM modules
                          WHERE md_active = 1 AND md_robots = 'index, follow'";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $url['uri'] = $row['md_url'];
            $url['full'] = "$baseUrl/{$row['md_url']}";
            if ($row['md_url'] !== 'index.html') {
                $url['full'] .= '/';
            }
            $url['lastmod'] = $row['date_update'];
            $url['freq'] = 'weekly';
            $url['priority'] = ($row['md_url'] !== 'index.html') ? '0.90' : '1.00';
            $urls[] = $url;
        }

        $this->db->sql = "SELECT u.url, DATE_FORMAT(c.pc_lastup_date, '%Y-%m-%dT%H:%i:%s+00:00') date_update
            FROM region_url u
                LEFT JOIN pagecity c ON c.pc_id = u.citypage
            WHERE c.pc_text IS NOT NULL
            ORDER BY c.pc_lastup_date DESC";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $url['uri'] = $row['url'];
            $url['full'] = "$baseUrl{$row['url']}/";
            $url['lastmod'] = $row['date_update'];
            $url['freq'] = 'weekly';
            $url['priority'] = '0.80';
            $urls[] = $url;
        }

        $this->db->sql = "SELECT concat(u.url, '/', p.pt_slugline, '.html') url,
                                 DATE_FORMAT(p.pt_lastup_date, '%Y-%m-%dT%H:%i:%s+00:00') date_update
            FROM pagepoints p
                LEFT JOIN pagecity c ON c.pc_id = p.pt_citypage_id
                    LEFT JOIN region_url u ON u.uid = c.pc_url_id
            WHERE pt_deleted_at IS NULL
            ORDER BY p.pt_lastup_date DESC";
        $this->db->exec();
        while ($row = $this->db->fetch()) {
            $url['uri'] = $row['url'];
            $url['full'] = "$baseUrl{$row['url']}";
            $url['lastmod'] = $row['date_update'];
            $url['freq'] = 'monthly';
            $url['priority'] = '0.70';
            $urls[] = $url;
        }

        $this->smarty->assign('urls', $urls);

        $fileContent = $this->smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_XML/sitemap.sm.xml');

        chmod($filename, 0777);
        $fileHandler = fopen((string) $filename, 'wb+');
        if (!$fileHandler) {
            throw new CrontabException('Ошибка доступа к файлу!');
        }
        fwrite($fileHandler, $fileContent);
        fclose($fileHandler);

        $filesizeNew = filesize($filename);

        if ($filesizeNew !== $filesizeOld) {
            $this->notifyGoogle();
        }
    }

    private function notifyGoogle(): void
    {
        $ch = curl_init();
        $url = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=https://culttourism.ru/sitemap.xml';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
    }
}
