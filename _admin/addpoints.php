<?php

use app\checker\FeedbackSpamChecker;
use app\core\GlobalConfig;
use app\exceptions\NotFoundException;
use app\model\repository\CandidateDomainBlacklistRepository;
use app\utils\JSON;
use GuzzleHttp\Client;
use YandexSearchAPI\Result;
use YandexSearchAPI\SearchException;
use YandexSearchAPI\SearchRequest;
use YandexSearchAPI\YandexSearchService;

require_once '_common.php';

$smarty->assign('title', 'Заявки на добавление');

$c = new MCandidatePoints($db);
$dc = new MDataCheck($db);

if (isset($_GET['id'], $_GET['act'])) {
    $out = [
        'state' => false,
        'id' => (int) $_GET['id'],
        'data' => null,
        'error' => null,
    ];
    switch ($_GET['act']) {
        case 'hash':
            $list = $c->getByFilter(['noHash' => 1]);
            foreach ($list as $item) {
                $hash = $c->getHash((int) $item['cp_id']);
                $c->updateByPk($item['cp_id'], ['cp_hash' => $hash]);
            }
            $out['state'] = true;
            break;
        case 'set_type':
            $out['state'] = $c->updateByPk(
                $out['id'],
                [
                    'cp_type_id' => (int) $_GET['ptype'],
                    'cp_state' => 25,
                ]
            );
            break;
        case 'get_analogs':
            $searchQuery = $_GET['pname'] . ' host:culttourism.ru';
            $searchService = new YandexSearchService(new Client(), $logger);
            $searchService->setApiId(YANDEX_SEARCH_ID);
            $searchService->setApiKey(YANDEX_SEARCH_KEY);

            $searchQuery = new SearchRequest($searchQuery);
            $searchQuery->setPage(0);
            try {
                $searchResult = $searchService->search($searchQuery);

                $out['founded'] = array_map(
                    static function (Result $item) {
                        return [
                            'url' => $item->getUrl(),
                            'title' => $item->getTitle(),
                        ];
                    },
                    $searchResult->getResults()
                );
                $out['state'] = true;
                $out['error'] = null;
            } catch (SearchException $exception) {
                $out['founded'] = [];
                $out['state'] = false;
                $out['error'] = $exception->getMessage();
            }
            break;
        case 'citysuggest':
            $pc = new MPageCities($db);
            $out['query'] = htmlentities(trim($_GET['query']), ENT_QUOTES, 'UTF-8');
            $out['suggestions'] = [];
            $variants = $pc->getSuggestion($out['query']);
            foreach ($variants as $variant) {
                $out['suggestions'][] = [
                    'value' => (string) ($variant['pc_title']),
                    'pcid' => (string) ($variant['pc_id']),
                    'url' => "{$variant['url']}/",
                ];
            }
            break;
        case 'set_citypage':
            $out['state'] = $c->updateByPk(
                $out['id'],
                [
                    'cp_citypage_id' => (int) $_GET['pc_id'],
                    'cp_state' => 25,
                ]
            );
            break;
        case 'get_citypage':
            $pc = new MPageCities($db);
            $out['citypage'] = $pc->getItemByPk((int) $_GET['pc_id']);
            $out['state'] = true;
            break;
        case 'save_candidate':
            $out['state'] = $c->updateByPk(
                $out['id'],
                [
                    'cp_title' => trim($_POST['title']),
                    'cp_text' => trim($_POST['text']),
                    'cp_addr' => trim($_POST['addr']),
                    'cp_phone' => trim($_POST['phone']),
                    'cp_worktime' => trim($_POST['worktime']),
                    'cp_web' => trim($_POST['web']),
                    'cp_latitude' => cut_trash_float($_POST['lat']),
                    'cp_longitude' => cut_trash_float($_POST['lon']),
                    'cp_zoom' => cut_trash_float($_POST['zoom']),
                    'cp_state' => (int) $_POST['state_id'],
                ]
            );
            $dc->deleteChecked(MDataCheck::ENTITY_CANDIDATES, $out['id']);
            break;
        case 'set_ignore':
            $newStateId = (int) $_GET['state_id'];
            $out['state'] = $c->updateByPk(
                $out['id'],
                [
                    'cp_state' => $newStateId,
                    'cp_active' => 0,
                ]
            );
            if ($newStateId === MCandidatePoints::STATUS_SPAM) {
                $spamDomainsRepository = new CandidateDomainBlacklistRepository($db);
                $domain = FeedbackSpamChecker::getDomain($_GET['web'] ?? '');
                $domainEntity = $spamDomainsRepository->getEntityByDomain($domain);
                if ($domainEntity !== null) {
                    $domainEntity->active = 1;
                    $spamDomainsRepository->save($domainEntity);
                }
            }
            break;
        case 'move':
            $pt = new MPagePoints($db);
            $candidate = $c->getItemByPk($out['id']);
            if (mb_strlen($candidate['cp_title'], 'utf-8') <= 4) {
                $out['error'][] = 'Слишком короткое название (минимум 4 символа)';
            }
            if ((int) $candidate['cp_citypage_id'] === 0) {
                $out['error'][] = 'Не указана страница назначения';
            }
            if ((int) $candidate['cp_type_id'] === 0) {
                $out['error'][] = 'Не указан тип';
            }
            if (empty($out['error'])) {
                $new_id = $pt->insert(
                    [
                        'pt_name' => $candidate['cp_title'],
                        'pt_description' => $candidate['cp_text'],
                        'pt_citypage_id' => $candidate['cp_citypage_id'],
                        'pt_latitude' => $candidate['cp_latitude'],
                        'pt_longitude' => $candidate['cp_longitude'],
                        'pt_latlon_zoom' => $candidate['cp_zoom'],
                        'pt_type_id' => $candidate['cp_type_id'],
                        'pt_website' => $candidate['cp_web'],
                        'pt_worktime' => $candidate['cp_worktime'],
                        'pt_adress' => $candidate['cp_addr'],
                        'pt_phone' => $candidate['cp_phone'],
                        'pt_email' => $candidate['cp_email'],
                        'pt_is_best' => 0,
                    ]
                );
                $out['state'] = $c->updateByPk(
                    $out['id'],
                    [
                        'cp_state' => 6,
                        'cp_point_id' => $new_id,
                        'cp_active' => 0,
                    ]
                );
            }
            break;
        default:
            throw new NotFoundException();
    }
    $out['data'] = $c->getItemByPk($out['id']);
    JSON::echo($out);
} elseif (isset($_GET['id']) && !isset($_GET['act'])) {
    $rpt = new MRefPointtypes($db);

    $globalConfig = new GlobalConfig($db);

    $item = $c->getItemByPk((int) $_GET['id']);

    $smarty->assign('claim', $item);
    $smarty->assign('referer', $_SERVER['HTTP_REFERER'] ?? 'addpoints.php');
    $smarty->assign('ref_types', $rpt->getActive());
    $smarty->assign('yandex_maps_key', $globalConfig->getYandexMapsKey());
    // -----------   обработка заявки ----------
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/pointadding.item.tpl'));
} else {
    // -----------   список активных ----------
    $pt = new MRefPointtypes($db);
    $uv_st = new UnirefValues($db, 3);

    $filter = [
        'active' => 1,
        'type' => isset($_GET['type']) ? (int) $_GET['type'] : 0,
        'pcid' => isset($_GET['pcid']) ? (int) $_GET['pcid'] : 0,
        'state' => isset($_GET['state']) ? (int) $_GET['state'] : 0,
        'gps' => isset($_GET['gps']) ? (int) $_GET['gps'] : 0,
    ];

    $list = $c->getByFilter($filter);
    $ref_pc = [];
    foreach ($list as $li) {
        $ref_pc[$li['cp_citypage_id']] = [
            'id' => $li['cp_citypage_id'],
            'title' => $li['page_title'] ?: '-не указано-'
        ];
    }
    asort($ref_pc);

    $matrix = $c->getMatrix();

    $smarty->assign('filter', $filter);
    $smarty->assign('ref_pt', $pt->getActive());
    $smarty->assign('ref_pc', $ref_pc);
    $smarty->assign('ref_st', $uv_st->getActive());
    $smarty->assign('matrix', $matrix);
    $smarty->assign('list', $list);
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/pointadding.list.tpl'));
}


$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
