<?php

/**
 * Пересчет статистических данных по количеству городов и точек
 */
$sp = new MSysProperties($db);
$pc = new MPageCities($db);
$pt = new MPagePoints($db);

$cnt_pc = $pc->getCount();
$cnt_pt = $pt->getCount();

//...о 8881 достопримечательностях в 283 городах и регионах
$text = $cnt_pt . ' '
        . Helper::getNumEnding($cnt_pt, array('достопримечательности', 'достопримечательностях', 'достопримечательностях'))
        . ' в ' . $cnt_pc . ' '
        . Helper::getNumEnding($cnt_pc, array('городе', 'городах', 'городах')) . ' и регионах';
$sp->updateByName('stat_text', $text);

$pc->updateStat();
