<?php

declare(strict_types=1);

namespace app\crontab;

use app\checker\DataChecker;
use MBlogEntries;
use MCandidatePoints;
use MLists;
use MPageCities;
use MPagePoints;
use Throwable;

class RepairDataCommand extends AbstractCrontabCommand
{
    private const CLEAN_AGE = 60;

    private const COUNT_ADDRESSES = 30;
    private const COUNT_COORDINATES = 30;
    private const COUNT_PHONES = 50;
    private const COUNT_CANDIDATES = 50;
    private const COUNT_CANDIDATE_ADDRESSES = 50;
    private const COUNT_BLOG = 50;
    private const COUNT_POINTS = 50;
    private const COUNT_CITIES = 50;

    private DataChecker $checker;
    private MPagePoints $pagePoints;
    private MCandidatePoints $candidatePoints;
    private MPageCities $pageCities;
    private MLists $lists;
    private MBlogEntries $blogEntries;

    public function __construct(
        DataChecker $checker,
        MPagePoints $pt,
        MCandidatePoints $ca,
        MPageCities $pc,
        MLists $ls,
        MBlogEntries $bg
    ) {
        $this->checker = $checker;
        $this->pagePoints = $pt;
        $this->candidatePoints = $ca;
        $this->pageCities = $pc;
        $this->lists = $ls;
        $this->blogEntries = $bg;
    }

    public function run(): void
    {
        $log = [];

        $this->checker->resetOldData('pagepoints', 'pt_latitude', self::CLEAN_AGE);

        $log[] = $this->checker->repairPointsAddresses(self::COUNT_ADDRESSES);
        $log[] = $this->checker->repairPointsCoordinates(self::COUNT_COORDINATES);
        $log[] = $this->checker->repairPointsPhones(self::COUNT_PHONES);
        $log[] = $this->checker->repairCandidates(self::COUNT_CANDIDATES);
        $log[] = $this->checker->repairCandidatesAddresses(self::COUNT_CANDIDATE_ADDRESSES);
        $log[] = $this->checker->repairBlog(self::COUNT_BLOG);
        $log[] = $this->checker->repairPoints(self::COUNT_POINTS);
        $log[] = $this->checker->repairCity(self::COUNT_CITIES);

        $this->pagePoints->repairData();
        $this->candidatePoints->repairData();

        $this->pagePoints->repairLinksAbsRel();
        $this->pageCities->repairLinksAbsRel();
        $this->lists->repairLinksAbsRel();
        $this->blogEntries->repairLinksAbsRel();

        $this->blogEntries->detectPictures();

        $this->processLogs($log);
    }

    private function processLogs(array $logs): void
    {
        $logs = array_filter($logs);
        if (!empty($logs)) {
            print_r($logs);
        }
    }
}
