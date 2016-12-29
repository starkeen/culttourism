<?php

class MBlogEntries extends Model {

    protected $_table_pk = 'br_id';
    protected $_table_order = 'br_date';
    protected $_table_active = 'br_active';

    public function __construct($db) {
        $this->_table_name = $db->getTableName('blogentries');
        $this->_table_fields = array(
            'br_date',
            'br_title',
            'br_url',
            'br_text',
            'br_picture',
            'br_us_id',
            'br_active',
        );
        parent::__construct($db);
        $this->_addRelatedTable('users');
    }

    /**
     * Последние несколько записей из блога
     * @param int $qnt
     * @return array
     */
    public function getLastActive($qnt = 10) {
        $this->_db->sql = "SELECT bg.br_id, bg.br_title, bg.br_text,
                                REPLACE(bg.br_text, '=\"/', CONCAT('=\"', :site_url1)) AS br_text_absolute,
                                'Роберт' AS us_name, 'abuse@culttourism.ru' AS us_email,
                                DATE_FORMAT(bg.br_date,'%a, %d %b %Y %H:%i:%s GMT') as bg_pubdate,
                                DATE_FORMAT(bg.br_date,'%d.%m.%Y') as bg_datex,
                                IF(bg.br_url != '',
                                    CONCAT(:site_url2, 'blog/', DATE_FORMAT(bg.br_date,'%Y/%m/'), bg.br_url, '.html'),
                                    CONCAT(DATE_FORMAT(bg.br_date,'%Y/%m/%d'),'.html')
                                ) as br_link
                            FROM $this->_table_name AS bg
                            WHERE br_active = 1
                                AND br_date < NOW()
                            ORDER BY bg.br_date DESC
                            LIMIT :limit";
        $this->_db->execute(array(
            ':site_url1' => _SITE_URL,
            ':site_url2' => _SITE_URL,
            ':limit' => $qnt,
        ));
        return $this->_db->fetchAll();
    }

    public function getLastWithTS($limit) {
        $out = array(
            'blogentries' => array(),
            'max_ts' => 0,
        );
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
        $this->_db->execute(array(
            ':limit' => intval($limit),
        ));
        $patern = "/(.*?)<\/p>/i";
        while ($row = $this->_db->fetch()) {
            $matches = array();
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
    public function repairLinksAbsRel() {
        $this->_db->sql = "UPDATE $this->_table_name
                            SET br_text = REPLACE(br_text, '=\"http://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();

        $this->_db->sql = "UPDATE $this->_table_name
                            SET br_text = REPLACE(br_text, '=\"https://" . _URL_ROOT . "/', '=\"/')";
        $this->_db->exec();
    }

    /**
     * По всем записям проставляет главную картинку (ссылку)
     */
    public function detectPictures() {
        $this->_db->sql = "SELECT bg.*
                            FROM $this->_table_name bg
                             WHERE bg.br_picture = ''
                             ORDER BY bg.br_date DESC
                             LIMIT :limit";
        $this->_db->execute(array(
            ':limit' => 20,
        ));
        $items = $this->_db->fetchAll();
        foreach ($items as $item) {
            $matches = array();
            preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $item['br_text'], $matches);
            $url = !empty($matches[1]) ? $matches[1] : '';
            if (substr($url, 0, 1) == '/') {
                $url = "https://" . _URL_ROOT . $url;
            }
            $this->updateByPk($item['br_id'], array(
                'br_picture' => $url,
            ));
        }
    }

}
