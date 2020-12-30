<?php

use app\core\assets\AssetsServiceBuilder;

$static = AssetsServiceBuilder::build()->rebuildAll();

$sp = new MSysProperties($db);
if (isset($static['css']['common'])) {
    $sp->updateByName('mainfile_css', basename($static['css']['common']));
}
if (isset($static['js']['common'])) {
    $sp->updateByName('mainfile_js', basename($static['js']['common']));
}

foreach ($static as $type => $packs) {
    foreach ($packs as $pack => $file) {
        $sp->updateByName('res_' . $type . '_' . $pack, basename($file));
    }
}
