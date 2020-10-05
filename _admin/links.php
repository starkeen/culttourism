<?php

declare(strict_types=1);

use app\sys\TemplateEngine;
use models\MLinks;

include('common.php');
include (_DIR_INCLUDES . '/class.Pager.php');

/** @var TemplateEngine $smarty */
$smarty->assign('title', 'Ссылки для ручной проверки');

$linksModel = new MLinks($db);

$urls = $linksModel->getList(1000);
$pager = new Pager($urls);

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
    if ($link['status'] === 301) {
        $currentUrlScheme = parse_url($link['url'], PHP_URL_SCHEME);
        $currentUrlDomain = parse_url($link['url'], PHP_URL_HOST);
        $redirectUrlScheme = parse_url($link['redirect_url'], PHP_URL_SCHEME);
        $redirectUrlDomain = parse_url($link['redirect_url'], PHP_URL_HOST);

        if (strpos($currentUrlDomain, 'www.') === 0) {
            $currentUrlDomain = str_replace('www.', '', $currentUrlDomain);
        }
        if (strpos($redirectUrlDomain, 'www.') === 0) {
            $redirectUrlDomain = str_replace('www.', '', $redirectUrlDomain);
        }

        if ($redirectUrlDomain === $currentUrlDomain && $redirectUrlScheme !== $currentUrlScheme) {
            $link['process_redirect'] = true;
        }
    }

    $links[] = $link;
}

$smarty->assign('links', $links);
$smarty->assign('pager', $pager->pages);
$smarty->assign('content', $smarty->fetch(_DIR_TEMPLATES . '/_admin/links.list.tpl'));

$smarty->display(_DIR_TEMPLATES . '/_admin/admpage.sm.html');
exit();
