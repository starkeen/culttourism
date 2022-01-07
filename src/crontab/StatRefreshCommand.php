<?php

declare(strict_types=1);

namespace app\crontab;

use app\utils\NumberEnding;
use MPageCities;
use MPagePoints;
use MSysProperties;

/**
 * Пересчет статистических данных по количеству городов и точек
 */
class StatRefreshCommand extends AbstractCrontabCommand
{
    private MPageCities $pc;
    private MPagePoints $pt;
    private MSysProperties $sp;

    public function __construct(MPageCities $pc, MPagePoints $pt, MSysProperties $sp)
    {
        $this->pc = $pc;
        $this->pt = $pt;
        $this->sp = $sp;
    }

    public function run(): void
    {
        $cnt_pc = $this->pc->getCount();
        $cnt_pt = $this->pt->getCount();

        //...о 8881 достопримечательностях в 283 городах и регионах
        $text = $cnt_pt
            . ' '
            . NumberEnding::getNumEnding($cnt_pt, ['достопримечательности', 'достопримечательностях', 'достопримечательностях'])
            . ' в '
            . $cnt_pc
            . ' '
            . NumberEnding::getNumEnding($cnt_pc, ['городе', 'городах', 'городах'])
            . ' и регионах';
        $this->sp->updateByName('stat_text', $text);

        $this->pc->updateStat();
        $this->pc->updateStatPhotos();
    }
}
