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

if ($act === 'process-redirect') {
    $id = (int) $_POST['id'];
    $record = $linksModel->getItemByPk($id);
    if ($record['redirect_url'] !== null) {
        $pointsModel->updateByPk(
            $record['id_object'],
            [
                'pt_website' => $record['redirect_url'],
            ]
        );
        $linksModel->deleteByPoint($record['id_object']);
    }
    $out = [
        'state' => true,
    ];
    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
} elseif ($act === 'process-delete') {
    $id = (int) $_POST['id'];
    $record = $linksModel->getItemByPk($id);
    $pointsModel->updateByPk(
        $record['id_object'],
        [
            'pt_website' => '',
        ]
    );
    $linksModel->deleteByPoint($record['id_object']);
    $out = [
        'state' => true,
    ];
    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
}

$urls = $linksModel->getHandProcessingList(1000, $status ?: null);
$pager = new Pager($urls);

$statuses = $linksModel->getHandProcessingStatuses();

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
$smarty->assign('status', $status);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/links.list.tpl'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');


exit();
