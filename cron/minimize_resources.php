<?php

$sr = new StaticResources();
$static = $sr->rebuildAll();

$sp = new MSysProperties($db);
if (isset($static['css']['common'])) {
    $sp->updateByPk(13, array('sp_value' => basename($static['css']['common'])));
}