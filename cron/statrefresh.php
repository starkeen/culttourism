<?php

/**
 * Пересчет статистических данных по количеству городов и точек
 */

use app\utils\NumberEnding;

$sp = new MSysProperties($db);
$pc = new MPageCities($db);
$pt = new MPagePoints($db);

$cnt_pc = $pc->getCount();
$cnt_pt = $pt->getCount();

//...о 8881 достопримечательностях в 283 городах и регионах
$text = $cnt_pt
    . ' '
    . NumberEnding::getNumEnding($cnt_pt, ['достопримечательности', 'достопримечательностях', 'достопримечательностях'])
    . ' в '
    . $cnt_pc
    . ' '
    . NumberEnding::getNumEnding($cnt_pc, ['городе', 'городах', 'городах'])
    . ' и регионах';
$sp->updateByName('stat_text', $text);

$pc->updateStat();
$pc->updateStatPhotos();
