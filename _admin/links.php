<?php

declare(strict_types=1);

use app\sys\TemplateEngine;
use models\MLinks;

include('common.php');
include(_DIR_INCLUDES . '/class.Pager.php');

/** @var TemplateEngine $smarty */
$smarty->assign('title', 'Ссылки для ручной проверки');

$linksModel = new MLinks($db);
$pointsModel = new MPagePoints($db);

$act = $_GET['act'] ?? null;
$status = (int) ($_GET['status'] ?? null);
$type = (int) ($_GET['type'] ?? null);

if ($act === 'process-redirect') {
    $id = (int) $_POST['id'];
    $state = false;
    $record = $linksModel->getItemByPk($id);
    if ($record['redirect_url'] !== null) {
        $pointsModel->updateByPk(
            $record['id_object'],
            [
                'pt_website' => $record['redirect_url'],
            ]
        );
        $linksModel->deleteByPoint($record['id_object']);
        $state = true;
    }
    $out = [
        'state' => $state,
    ];
    answer($out);
} elseif ($act === 'process-delete') {
    $id = (int) $_POST['id'];
    $state = false;
    $record = $linksModel->getItemByPk($id);
    if (!empty($record['id_object'])) {
        $pointsModel->updateByPk(
            $record['id_object'],
            [
                'pt_website' => '',
            ]
        );
        $linksModel->deleteByPoint($record['id_object']);
        $state = true;
    }
    $out = [
        'state' => $state,
    ];
    answer($out);
} elseif ($act === 'process-edit') {
    $id = (int) $_POST['id'];
    $value = cut_trash_string($_POST['value']);
    $newValue = null;
    $state = false;
    $record = $linksModel->getItemByPk($id);
    if (!empty($record['id_object'])) {
        $pointsModel->updateByPk(
            $record['id_object'],
            [
                'pt_website' => $value,
            ]
        );
        $newPoint = $pointsModel->getItemByPk($record['id_object']);
        $newValue = $newPoint['pt_website'];
        $linksModel->deleteByPoint($record['id_object']);
        $state = true;
    }
    $out = [
        'state' => $state,
        'value' => $newValue,
    ];
    answer($out);
} elseif ($act === 'process-disable') {
    $id = (int) $_POST['id'];
    $state = false;
    $record = $linksModel->getItemByPk($id);
    if (!empty($record['id_object'])) {
        $point = $pointsModel->getItemByPk($record['id_object']);
        $pointsModel->updateByPk(
            $record['id_object'],
            [
                'pt_website' => '',
                'pt_description' => $point['pt_description'] . PHP_EOL . '<p>Больше не работает</p>',
            ]
        );
        $pointsModel->deleteByPk($record['id_object']);
        $linksModel->deleteByPoint($record['id_object']);
        $state = true;
    }
    $out = [
        'state' => $state,
    ];
    answer($out);
}

$urls = $linksModel->getHandProcessingList(1000, $status ?: null, $type ?: null);
$pager = new Pager($urls);

$statuses = $linksModel->getHandProcessingStatuses();
$types = $linksModel->getHandProcessingTypes();

$links = [];
foreach ($pager->out as $link) {
    if ($link['status'] >= 500) {
        $link['status_class'] = 'status-fatal';
    } elseif ($link['status'] >= 400) {
        $link['status_class'] = 'status-error';
    } elseif ($link['status'] >= 300) {
        $link['status_class'] = 'status-redirect';
    } else {
        $link['status_class'] = 'status-ok';
    }

    $link['process_redirect'] = false;
    if ($link['status'] === 301 && $link['redirect_url'] !== null) {
        $currentUrlDomain = parse_url($link['url'], PHP_URL_HOST);
        $redirectUrlDomain = parse_url($link['redirect_url'], PHP_URL_HOST);

        if (strpos($currentUrlDomain, 'www.') === 0) {
            $currentUrlDomain = str_replace('www.', '', $currentUrlDomain);
        }
        if (strpos($redirectUrlDomain, 'www.') === 0) {
            $redirectUrlDomain = str_replace('www.', '', $redirectUrlDomain);
        }

        if (strtolower($redirectUrlDomain) === strtolower($currentUrlDomain)) {
            $link['process_redirect'] = true;
        }
    }

    $links[] = $link;
}

$smarty->assign('links', $links);
$smarty->assign('pager', $pager->pages);
$smarty->assign('statuses', $statuses);
$smarty->assign('types', $types);
$smarty->assign('status', $status);
$smarty->assign('type', $type);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/links.list.tpl'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');


exit();


function answer(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}