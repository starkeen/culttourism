<?php

error_reporting(E_ALL);

$pts = new Points($db);

foreach ($pts->getUnslug() as $point) {
    $name = trim($point['pt_name']);

    $analogs = $pts->searchByName($point['pt_name']);
    if ($point['tr_sight'] == 0 || count($analogs) > 1) {
        $name = $point['pc_title'] . ' ' . $name;
    }

    $name_url = preg_replace('/[^a-z0-9-_]+/', '', strtolower(Helper::getTranslit($name, '_')));
    $name_url = trim($name_url, '_-');

    $concurents = $pts->searchSlugline($name_url);
    if (count($concurents) > 0) {
        $name_url = trim(strtolower(trim($point['pc_title_english'])) . '_' . $name_url);
    }
    $name_url = trim($name_url, '_-');

    $concurents_else = $pts->searchSlugline($name_url);
    if (count($concurents_else) > 0) {
        $name_url .= '_' . count($concurents_else);
    }
    $name_url = trim($name_url, '_-');

    $concurents_last = $pts->searchSlugline($name_url);
    if (count($concurents_else) > 0) {
        $name_url .= '_' . $point['pt_id'];
    }
    $name_url = trim($name_url, '_-');

    $pts->updateByPk($point['pt_id'], array('pt_slugline' => $name_url));
}
