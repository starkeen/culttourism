<?php

use app\api\yandex_search\Factory;
use app\core\SiteRequest;
use app\db\MyDB;

class Page extends PageCommon
{
    /**
     * @inheritDoc
     */
    protected function compileContent(): void
    {
        if ($this->siteRequest->getLevel2() !== null) {
            $this->processError(Core::HTTP_CODE_404);
        }
        if ($this->siteRequest->getLevel1() === 'suggest' && isset($_GET['query'])) {
            $this->getSuggests();
        } elseif ($this->siteRequest->getLevel1() === 'suggest-object' && isset($_GET['query'])) {
            $this->getObjectSuggests();
        }
        $this->content = $this->getSearchYandex();
    }

    /**
     * Саджест регионов
     */
    private function getSuggests(): void
    {
        $out = [
            'query' => '',
            'suggestions' => [],
        ];
        $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
        $pc = new MPageCities($this->db);
        $variants = $pc->getSuggestion($out['query']);

        foreach ($variants as $row) {
            $out['suggestions'][] = [
                'value' => (string) ($row['pc_title']),
                'data' => $row['pc_id'],
                'url' => $row['url'] . '/',
            ];
        }

        header('Content-type: application/json');
        echo json_encode($out);
        exit();
    }

    /**
     * Саджест объектов
     */
    private function getObjectSuggests(): void
    {
        $out = [
            'query' => '',
            'suggestions' => [],
        ];
        $out['query'] = htmlentities(cut_trash_string($_GET['query']), ENT_QUOTES, "UTF-8");
        $pt = new MPagePoints($this->db);
        $variants = $pt->getSuggestion($out['query']);

        foreach ($variants as $row) {
            $out['suggestions'][] = [
                'value' => $row['pt_name'],
                'data' => $row['pt_id'],
                'city_id' => $row['pc_id'],
                'city_title' => $row['pc_title'],
                'latitude' => $row['pt_latitude'],
                'longitude' => $row['pt_longitude'],
            ];
        }

        header('Content-type: application/json');
        echo json_encode($out);
        exit();
    }

    private function getSearchYandex()
    {
        if (isset($_GET['q'])) {
            $query = htmlentities(cut_trash_string(strip_tags($_GET['q'])), ENT_QUOTES, "UTF-8");

            $this->log($query);

            $errorText = '';
            $result = [];
            $resultMeta = [
                'query' => $query,
                'page' => array_key_exists('page', $_GET) ? (int) $_GET['page'] : 0,
                'per_page' => 15, // документов на странице
                'pages_all' => 0, // всего доступно страниц
                'total' => 0, // всего найдено документов
                'resolution' => '', // результаты поиска текстом
                'text_source' => '', // в случае исправлений: исходный текст
                'text_result' => '', // в случае исправлений: исправленный текст
            ];

            $searchKeywords = $query . ' host:culttourism.ru';
            $yandexSearcher = Factory::build();
            $yandexSearcher->setDocumentsOnPage($resultMeta['per_page']);

            $searchResult = $yandexSearcher->searchPages($searchKeywords, $resultMeta['page']);

            if (!$searchResult->isError()) {
                foreach ($searchResult->getItems() as $resultItem) {
                    $titleItemElements = explode($this->globalConfig->getTitleDelimiter(), $resultItem->getTitle());
                    if (count($titleItemElements) > 1) {
                        array_pop($titleItemElements);
                    }

                    $result[] = [
                        'title' => str_replace(' , ', ', ', trim(implode(', ', $titleItemElements))),
                        'descr' => $resultItem->getDescription(),
                        'url' => $resultItem->getUrl(),
                    ];
                }
                $resultMeta['pages_all'] = $searchResult->getPagesCount();
                $resultMeta['total'] = $searchResult->getDocumentsCount();
                $resultMeta['resolution'] = str_replace('нашёл', '', $searchResult->getHumanResolution());

                $correctionInfo = $searchResult->getCorrection();
                if ($correctionInfo !== null) {
                    $resultMeta['text_source'] = $correctionInfo->getSourceText();
                    $resultMeta['text_result'] = $correctionInfo->getResultText();
                }
            } else {
                $errorText = $searchResult->getErrorText();
                $loggerContext = [
                    'query' => $searchKeywords,
                    'error_text' => $searchResult->getErrorText(),
                    'limit' => $yandexSearcher->getCurrentLimit(),
                ];
                $this->logger->warning('Ошибка в поиске', $loggerContext);
            }

            $this->smarty->assign('search', $query);
            $this->smarty->assign('error', $errorText);
            $this->smarty->assign('result', $result);
            $this->smarty->assign('meta', $resultMeta);
        } else {
            $this->smarty->assign('search', '');
            $this->smarty->assign('error', '');
            $this->smarty->assign('result', '');
            $this->smarty->assign('meta', []);
        }
        return $this->smarty->fetch(_DIR_TEMPLATES . '/search/search.sm.html');
    }

    private function log(string $query): void
    {
        $sc = new MSearchCache($this->db);
        $sc->add(
            [
                'sc_session' => $this->getUserHash(),
                'sc_query' => $query,
                'sc_sr_id' => null,
            ]
        );
    }

    private function getSearchInternal()
    {
        if (isset($_GET['q'])) {
            $q = cut_trash_string($_GET['q']);

            $q = $this->db->getEscapedString($q);
            $this->smarty->assign('search', $q);
            $this->pageContent->getHead()->addTitleElement($q);

            if (mb_strlen($q) >= 2) {
                $q = mb_strtolower($q);
                $q = str_replace('-', ' ', $q);
                $q = str_replace('&mdash;', ' ', $q);
                $q = str_replace('&ndash;', ' ', $q);
                $q = str_replace('&nbsp;', ' ', $q);
                $_query = explode(' ', $q);
                $_query = array_merge($_query, explode(' ', translit($q, ' ')));
                $_query = array_values($_query);

                $dbc = $this->db->getTableName('pagecity');
                $dbu = $this->db->getTableName('region_url');
                $dbsc = $this->db->getTableName('search_cache');

                $fields = [
                    ['field' => 'c.pc_title', 'weight' => 80,],
                    ['field' => 'c.pc_keywords', 'weight' => 70,],
                    ['field' => 'c.pc_description', 'weight' => 70,],
                    ['field' => 'c.pc_text', 'weight' => 10,],
                    ['field' => 'c.pc_inwheretext', 'weight' => 30,],
                    ['field' => 'c.pc_title_translit', 'weight' => 30,],
                    ['field' => 'c.pc_title_english', 'weight' => 50,],
                    ['field' => 'c.pc_title_synonym', 'weight' => 60,],
                ];
                $_where = [];
                foreach ($_query as $word) {
                    foreach ($fields as $field) {
                        $_where[$field['field']][] = "({$field['field']} LIKE '%$word%')";
                    }
                }

                $this->db->sql = "INSERT INTO $dbsc SET
                            sc_date = now(), sc_session = '" . $this->getUserHash(
                    ) . "', sc_query = '$q', sc_sr_id = null";
                $this->db->exec();

                $this->db->sql = "SELECT c.pc_id, c.pc_title, u.url, c.pc_text, c.pc_rank, 100 AS weight
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
                $this->db->sql .= implode(' OR ', $_where_x);
                $this->db->sql .= "\n
                            UNION \n\n";

                $_sql = [];
                foreach ($fields as $field) {
                    $where = implode(' OR ', $_where[$field['field']]);
                    $_sql[] = "SELECT c.pc_id, c.pc_title, u.url, c.pc_text, c.pc_rank, {$field['weight']} AS weight
                                FROM $dbc c
                                LEFT JOIN $dbu u ON c.pc_url_id = u.uid
                                WHERE $where\n";
                }
                $sql_subs = implode("\n UNION \n\n", $_sql);

                $this->db->sql .= $sql_subs;
                $this->db->sql .= "GROUP BY pc_id, url
                             ORDER BY weight DESC, pc_rank DESC, pc_title";
                $this->db->exec();
                $result = [];
                while ($row = $this->db->fetch()) {
                    $result[$row['pc_id']] = [
                        'title' => $row['pc_title'],
                        'url' => $row['url'],
                        'descr' => mb_substr(
                                strip_tags($row['pc_text']),
                                0,
                                mb_strpos(strip_tags($row['pc_text']), ' ', 250)
                            ) . '...'
                    ];
                    $patterns = [];
                    $replacements = [];
                    foreach ($_query as $word) {
                        $word = preg_replace('/[абвг]/iu', '[абвг]', $word);
                        $patterns[] = "#$word#iu";
                        $replacements[] = '<b>$0</b>';
                    }
                    $result[$row['pc_id']]['title'] = preg_replace(
                        $patterns,
                        $replacements,
                        $result[$row['pc_id']]['title']
                    );
                    $result[$row['pc_id']]['descr'] = preg_replace(
                        $patterns,
                        $replacements,
                        $result[$row['pc_id']]['descr']
                    );
                }
                $this->smarty->assign('result', $result);
            } else {
                $this->smarty->assign('error', 'Слишком короткий запрос');
            }
        } else {
            $this->smarty->assign('search', '');
            $this->smarty->assign('error', '');
            $this->smarty->assign('result', '');
        }
        return $this->smarty->fetch(_DIR_TEMPLATES . '/search/search.sm.html');
    }

    public static function getInstance(MyDB $db, SiteRequest $request): self
    {
        return self::getInstanceOf(__CLASS__, $db, $request);
    }
}
