<?php

use app\utils\JSON;

require_once '_common.php';

$smarty->assign('title', 'Списки объектов');

$lst = new MLists($db);

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $list = $lst->getItemByPk($id);

    $lstitems = new MListsItems($db, $id);

    if (isset($_POST) && !empty($_POST)) {
        if (isset($_GET['act'], $_POST['add_id']) && $_GET['act'] === 'add' && (int) $_POST['add_id'] > 0) {
            $res = $lstitems->insert(['li_ls_id' => $id, 'li_pt_id' => (int) $_POST['add_id']]);
            if ($res) {
                $lst->updateByPk($id, ['ls_update_date' => date('Y-m-D H:i:s')]);
                header("Location: lists.php?id=$id");
                exit();
            }
        } else {
            $upds = $_POST;
            $upds['ls_update_date'] = date('Y-m-d H:i:s');
            $res = $lst->updateByPk($id, $upds);
            if ($res) {
                header("Location: lists.php");
                exit();
            }
        }
    }

    $smarty->assign('list', $list);
    $smarty->assign('list_items', $lstitems->getAll());
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/lists.item.tpl'));
} elseif (isset($_GET['suggest'])) {
    $out = ['query' => '', 'suggestions' => []];
    $out['query'] = htmlentities(trim($_GET['query']), ENT_QUOTES, 'UTF-8');
    $lid = (int) $_GET['lid'];

    if (strlen($out['query']) > 4) {
        $lstitems = new MListsItems($db, $lid);
        $variants = $lstitems->getSuggestion($out['query']);
        foreach ($variants as $variant) {
            $out['suggestions'][] = [
                'value' => "{$variant['pt_name']} ({$variant['pc_title']})",
                'oid' => "{$variant['pt_id']}",
            ];
        }
        if (empty($out['suggestions'])) {
            $out['suggestions'][] = [
                'value' => '-- не найдено --',
                'oid' => '',
            ];
        }
    }
    JSON::echo($out);
} elseif (isset($_GET['json'])) {
    $out = ['state' => false, 'newval' => null];

    $lstitems = new MListsItems($db, (int) $_GET['lid']);
    $out['newval'] = $lstitems->setField($_GET['field'], (int) $_GET['ptid'], $_GET['val']);
    if ($out['newval'] !== null) {
        $out['state'] = true;
    }

    $lst->updateByPk((int) $_GET['lid'], ['ls_update_date' => date('Y-m-D H:i:s')]);

    JSON::echo($out);
} else {
    $smarty->assign('lists', $lst->getAll());
    $smarty->assign('content', $smarty->fetch(GLOBAL_DIR_TEMPLATES . '/_admin/lists.list.tpl'));
}

$smarty->display(GLOBAL_DIR_TEMPLATES . '/_admin/admpage.tpl');
exit();
