<?php

$sr = new StaticResources();
$static = $sr->rebuildAll();

$sp = new MSysProperties($db);
if (isset($static['css']['common'])) {
    $sp->updateByName('mainfile_css', basename($static['css']['common']));
}
if (isset($static['js']['common'])) {
    $sp->updateByName('mainfile_js', basename($static['js']['common']));
}