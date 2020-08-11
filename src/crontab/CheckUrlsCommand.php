<?php

declare(strict_types=1);

namespace app\crontab;

use app\db\MyDB;
use models\MLinks;
use Psr\Log\LoggerInterface;

class CheckUrlsCommand extends CrontabCommand
{
    /**
     * @var MLinks
     */
    private $linksModel;

    /**
     * @var MyDB
     */
    private $db;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(MLinks $linksModel, MyDB $db, LoggerInterface $logger)
    {
        $this->linksModel = $linksModel;
        $this->db = $db;
        $this->logger = $logger;
    }

    public function run(): void
    {
        $this->linksModel->makeCache();
    }

    private function legacy(): void
    {
        ini_set('max_execution_time', 1500);

        $allow_codes = [200, 302];
        $limit_once = 50;
        $useragent = "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17 AlexaToolbar/alxg-3.1";

        $dbp = $this->db->getTableName('pagepoints');
        $dbu = $this->db->getTableName('region_url');
        $dbsp = $this->db->getTableName('siteprorerties');

        $this->db->sql = "SELECT count(*) AS cnt FROM $dbp WHERE pt_website != ''";
        $this->db->exec();
        $xrow = $this->db->fetch();
        $lim_total = (int) $xrow['cnt'];

        $this->db->sql = "SELECT sp_value FROM $dbsp WHERE sp_id = 25";
        $this->db->exec();
        $row = $this->db->fetch();
        $lim_shift = (int) $row['sp_value'];

        $this->db->sql = "SELECT pt_id, pt_name, pt_citypage_id, pt_website, url.url
            FROM $dbp p
                LEFT JOIN $dbu url ON url.citypage = p.pt_citypage_id
            WHERE pt_website != ''
            ORDER BY url.url
            LIMIT $lim_shift, $limit_once";
        $this->db->exec();
        $errlog = [];
        while ($row = $this->db->fetch()) {
            $url = $row['pt_website'];
            $ch = curl_init("http://check-host.net/check-http?host=$url");
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $page_text = substr($content, strpos($content, 'get_check_results('));
            $page_text = substr($page_text, 0, strpos($page_text, 'check_displayer'));

            $keys = explode(',', trim(str_replace('get_check_results(', '', trim($page_text)), "'"));
            $key = str_replace([" ", "\n", "\t", "\r", "'"], '', trim(array_shift($keys)));
            $xslaves = explode('","', trim(implode(',', $keys), ',[]"'));
            array_shift($xslaves);
            $_postslaves = [];
            foreach ($xslaves as $xslave) {
                $_postslaves[] = "slaves[]=$xslave";
            }
            $postslaves = implode('&', $_postslaves);

            $ch = curl_init("http://check-host.net/check_result/$key");
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_REFERER, "http://check-host.net/check-http?host=$url");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postslaves);
            $content = curl_exec($ch);
            curl_close($ch);
            $states = [200 => 0];
            foreach (json_decode($content) as $out) {
                $state = $out[0][3];
                if (!isset($states[$state])) {
                    $states[$state] = 0;
                }
                $states[$state]++;
            }
            ksort($states);
            $http_status = array_search(max($states), $states);

            if (!in_array($http_status, $allow_codes)) {
                $errlog[] = str_replace(
                    ["\n", "\r", "\t"],
                    '',
                    "$http_status: {$row['pt_website']} - {$row['pt_name']} (http://culttourism.ru{$row['url']}/)"
                );
            }
        }

        $newshift = $lim_shift + $limit_once;
        if ($newshift >= $lim_total) {
            $newshift = 0;
        }

        $this->db->sql = "UPDATE $dbsp SET sp_value = '$newshift' WHERE sp_id = 25";
        $this->db->exec();

        if (count($errlog) > 0) {
            echo implode("\n", $errlog);
        }
    }
}
