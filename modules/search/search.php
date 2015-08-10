<?php

class Page extends PageCommon {

    public function __construct($db, $mod) {
        list($module_id, $page_id, $id) = $mod;
        global $smarty;
        parent::__construct($db, 'search');
        if ($id) {
            $this->getError('404');
        }
        if ($page_id == 'suggest' && isset($_GET['query'])) {
            $this->getSuggests($db);
        }
        $this->content = $this->getSearchYandex($db, $smarty);
    }

    private function getSuggests($db) {
        $out = array('query' => '', 'suggestions' => array());
        $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
        $pc = new MPageCities($db);
        $variants = $pc->getSuggestion($out['query']);

        foreach ($variants as $row) {
            $out['suggestions'][] = array(
                'value' => "{$row['pc_title']}",
                'data' => $row['pc_id'],
                'url' => $row['url'] . '/',
            );
        }

        header('Content-type: application/json');
        echo json_encode($out);
        exit();
    }

    private function getSearchYandex($db, $smarty) {
        $dbsc = $db->getTableName('search_cache');
        if (isset($_GET['q'])) {
            $query = htmlentities(cut_trash_string(strip_tags($_GET['q'])), ENT_QUOTES, "UTF-8");

            $db->sql = "INSERT INTO $dbsc SET
                            sc_date = now(), sc_session = '" . $this->getUserHash() . "', sc_query = '$query', sc_sr_id = null";
            $db->exec();

            $error_text = '';
            $result = array();
            $result_meta = array(
                'query' => $query,
                'page' => array_key_exists('page', $_GET) ? intval($_GET['page']) : 0,
                'pages_all' => 0,
            );

            $ys = new YandexSearcher();
            $ys->setPage($result_meta['page']);
            $ys->enableLogging($db);
            $res = $ys->search("$query host:culttourism.ru");
            if ($res['error_text']) {
                $error_text = trim(str_replace('starkeen', '', $res['error_text']));
            } else {
                foreach ($res['results'] as $result_item) {
                    $title_items = explode($this->globalsettings['title_delimiter'], $result_item['title_hw']);
                    array_pop($title_items);
                    $result_item['title'] = trim(implode(', ', $title_items));
                    $result_item['title'] = str_replace(' , ', ', ', $result_item['title']);
                    $result_item['descr'] = $result_item['descr_hw'];
                    unset($result_item['title_hw']);
                    unset($result_item['descr_hw']);
                    $result[] = $result_item;
                }
                $result_meta['pages_all'] = $res['pages_cnt'];
            }

            $result_meta['pages'] = 20;
            $result_meta['query'] = ($query);
            $result_meta['page'] = array_key_exists('page', $_GET) ? intval($_GET['page']) : 0;

            $smarty->assign('search', $query);
            $smarty->assign('error', $error_text);
            $smarty->assign('result', $result);
            $smarty->assign('meta', $result_meta);
        } else {
            $smarty->assign('search', '');
            $smarty->assign('error', '');
            $smarty->assign('result', '');
            $smarty->assign('meta', array());
        }
        return $smarty->fetch(_DIR_TEMPLATES . '/search/search.sm.html');
    }

    private function highlight_words($node) {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '</strong>', preg_replace('/<hlword[^>]*>/', '<strong>', $stripped));
    }

    private function getSearchInternal($db, $smarty) {
        if (isset($_GET['q'])) {
            $q = trim($q);
            $q = cut_trash_string($_GET['q']);

            $q = $db->getEscapedString($q);
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
                foreach ($_query as $word) {
                    foreach ($fields as $field) {
                        $_where[$field['field']][] = "({$field['field']} LIKE '%$word%')";
                    }
                }

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
                foreach ($fields as $field) {
                    $_where_x[] = "({$field['field']} LIKE '%$q%')";
                }
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