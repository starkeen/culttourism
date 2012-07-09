<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        parent::__construct($db, 'search');
        if ($id)
            $this->getError('404');
        $this->content = $this->getSearch($db, $smarty);
    }

    private function getSearch($db, $smarty) {
        if (isset($_GET['q'])) {
            $q = trim($q);
            $q = cut_trash_string($_GET['q']);
            $q = mysql_real_escape_string($q);
            $smarty->assign('search', $q);
            $this->addTitle($q);

            if (mb_strlen($q) >= 2) {
                $q = mb_strtolower($q);
                $q = str_replace('-', ' ', $q);
                $q = str_replace('&mdash;', ' ', $q);
                $q = str_replace('&ndash;', ' ', $q);
                $q = str_replace('&nbsp;', ' ', $q);
                $_query = explode(' ', $q);
                $_query = array_merge($_query, explode(' ', translit($q, ' ')));
                $_query = array_values($_query);

                $dbc = $db->getTableName('pagecity');
                $dbu = $db->getTableName('region_url');
                $dbsc = $db->getTableName('search_cache');

                $fields = array(
                    array('field' => 'c.pc_title', 'weight' => 80,),
                    array('field' => 'c.pc_keywords', 'weight' => 70,),
                    array('field' => 'c.pc_description', 'weight' => 70,),
                    array('field' => 'c.pc_text', 'weight' => 10,),
                    array('field' => 'c.pc_inwheretext', 'weight' => 30,),
                    array('field' => 'c.pc_title_translit', 'weight' => 30,),
                    array('field' => 'c.pc_title_english', 'weight' => 50,),
                    array('field' => 'c.pc_title_synonym', 'weight' => 60,),
                );
                $_where = array();
                foreach ($_query as $word)
                    foreach ($fields as $field)
                        $_where[$field['field']][] = "({$field['field']} LIKE '%$word%')";

                $db->sql = "INSERT INTO $dbsc SET
                            sc_date = now(), sc_session = '" . $this->getUserHash() . "', sc_query = '$q', sc_sr_id = null";
                $db->exec();

                $db->sql = "SELECT c.pc_id, c.pc_title, u.url, c.pc_text, c.pc_rank, 100 AS weight
                            FROM $dbc c
                            LEFT JOIN $dbu u ON c.pc_url_id = u.uid
                            WHERE c.pc_title LIKE '%$q%'
                            UNION
                            SELECT c.pc_id, c.pc_title, u.url, c.pc_text, c.pc_rank, 90 AS weight
                            FROM $dbc c
                            LEFT JOIN $dbu u ON c.pc_url_id = u.uid
                            WHERE ";
                foreach ($fields as $field)
                    $_where_x[] = "({$field['field']} LIKE '%$q%')";
                $db->sql .= implode(' OR ', $_where_x);
                $db->sql .= "\n
                            UNION \n\n";

                $_sql = array();
                foreach ($fields as $field) {
                    $where = implode(' OR ', $_where[$field['field']]);
                    $_sql[] = "SELECT c.pc_id, c.pc_title, u.url, c.pc_text, c.pc_rank, {$field['weight']} AS weight
                                FROM $dbc c
                                LEFT JOIN $dbu u ON c.pc_url_id = u.uid
                                WHERE $where\n";
                }
                $sql_subs = implode("\n UNION \n\n", $_sql);

                $db->sql .= $sql_subs;
                $db->sql .= "GROUP BY pc_id, url
                             ORDER BY weight DESC, pc_rank DESC, pc_title";
                //$db->showSQL();
                $db->exec();
                $result = array();
                while ($row = $db->fetch()) {
                    $result[$row['pc_id']] = array(
                        'title' => $row['pc_title'],
                        'url' => $row['url'],
                        'descr' => mb_substr(strip_tags($row['pc_text']), 0, mb_strpos(strip_tags($row['pc_text']), ' ', 250)) . '...'
                    );
                    $patterns = array();
                    $replacements = array();
                    foreach ($_query as $word) {
                        $word = preg_replace('/[абвг]/iu', '[абвг]', $word);
                        $patterns[] = "#$word#iu";
                        $replacements[] = '<b>$0</b>';
                    }
                    $result[$row['pc_id']]['title'] = preg_replace($patterns, $replacements, $result[$row['pc_id']]['title']);
                    $result[$row['pc_id']]['descr'] = preg_replace($patterns, $replacements, $result[$row['pc_id']]['descr']);
                }
                $smarty->assign('result', $result);
            } else {
                $smarty->assign('error', 'Слишком короткий запрос');
            }
        } else {
            $smarty->assign('search', '');
            $smarty->assign('error', '');
            $smarty->assign('result', '');
        }
        return $smarty->fetch(_DIR_TEMPLATES . '/search/search.sm.html');
    }

    public static function getInstance($db, $mod = null) {
        return self::getInstanceOf(__CLASS__, $db, $mod);
    }

}

?>