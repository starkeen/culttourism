<?php

use app\db\MyDB;

class MBlogEntries extends Model
{
    protected $_table_pk = 'br_id';
    protected $_table_order = 'br_date';
    protected $_table_active = 'br_active';

    public function __construct(MyDB $db)
    {
        $this->_table_name = $db->getTableName('blogentries');
        $this->_table_fields = [
            'br_date',
            'br_title',
            'br_url',
            'br_text',
            'br_picture',
            'br_us_id',
            'br_active',
        ];
        parent::__construct($db);
        $this->addRelatedTable('users');
    }

    /**
     * Последние несколько записей из блога
     *
     * @param int $qnt
     *
     * @return array
     */
    public function getLastActive($qnt = 10): array
    {
        $this->_db->sql = "SELECT bg.br_id, bg.br_title, bg.br_text, bg.br_date,
                                REPLACE(bg.br_text, '=\"/', CONCAT('=\"', :site_url1)) AS br_text_absolute,
                                'Роберт' AS us_name, 'abuse@culttourism.ru' AS us_email,
                                DATE_FORMAT(bg.br_date,'%a, %d %b %Y %H:%i:%s GMT') as bg_pubdate,
                                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                                IF(bg.br_url != '',
                                    CONCAT(:site_url2, 'blog/', DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'),
                                    CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')
                                ) as br_link,
                                REPLACE(bg.br_picture, '=\"/', CONCAT('=\"', :site_url3)) AS br_picture
                            FROM $this->_table_name AS bg
                            WHERE br_active = 1
                                AND br_date < NOW()
                            ORDER BY bg.br_date DESC
                            LIMIT :limit";
        $this->_db->execute(
            [
                ':site_url1' => GLOBAL_SITE_URL,
                ':site_url2' => GLOBAL_SITE_URL,
                ':site_url3' => GLOBAL_SITE_URL,
                ':limit' => $qnt,
            ]
        );

        return $this->_db->fetchAll();
    }

    public function getLastWithTS($limit): array
    {
        $out = [
            'blogentries' => [],
            'max_ts' => 0,
        ];
        $this->_db->sql = "SELECT bg.*, us.us_name,
                                UNIX_TIMESTAMP(bg.br_date) AS last_update,
                                DATE_FORMAT(bg.br_date,'%Y') as bg_year, DATE_FORMAT(bg.br_date,'%m') as bg_month,
                                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                                IF (bg.br_url != '', bg.br_url, DATE_FORMAT(bg.br_date,'%d')) as bg_day,
                                IF (bg.br_url != '', CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'), CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')) as br_link
                             FROM $this->_table_name bg
                                 LEFT JOIN {$this->_tables_related['users']} us ON bg.br_us_id = us.us_id
                             WHERE bg.br_date < NOW()
                             ORDER BY bg.br_date DESC
                             LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => (int) $limit,
            ]
        );
        $patern = "/(.*?)<\/p>/i";
        while ($row = $this->_db->fetch()) {
            $matches = [];
            preg_match_all($patern, $row['br_text'], $matches);
            if (isset($matches[0][0])) {
                $row['br_text'] = strip_tags($matches[0][0], '<p><a>');
            }
            $out['blogentries'][$row['br_id']] = $row;
            if ($row['last_update'] > $out['max_ts']) {
                $out['max_ts'] = $row['last_update'];
            }
        }
        return $out;
    }

    /**
     * Заменяет все абсолютные ссылки относительными
     */
    public function repairLinksAbsRel(): void
    {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET br_text = REPLACE(br_text, '=\"http://" . GLOBAL_URL_ROOT . "/', '=\"/')";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                            SET br_text = REPLACE(br_text, '=\"https://" . GLOBAL_URL_ROOT . "/', '=\"/')";
        $this->_db->exec();
    }

    /**
     * По всем записям проставляет главную картинку (ссылку)
     */
    public function detectPictures(): void
    {
        $this->_db->sql = "SELECT bg.*
                            FROM $this->_table_name bg
                             WHERE bg.br_picture = ''
                             ORDER BY bg.br_date DESC
                             LIMIT :limit";
        $this->_db->execute(
            [
                ':limit' => 20,
            ]
        );
        $items = $this->_db->fetchAll();
        foreach ($items as $item) {
            $matches = [];
            preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $item['br_text'], $matches);
            $url = !empty($matches[1]) ? $matches[1] : '';
            if (substr($url, 0, 1) == '/') {
                $url = "https://" . GLOBAL_URL_ROOT . $url;
            }
            $this->updateByPk(
                $item['br_id'],
                [
                    'br_picture' => $url,
                ]
            );
        }
    }
}
