<?php

declare(strict_types=1);

namespace app\crontab;

use app\rss\IRSSGenerator;
use app\rss\RSSAddUTM;
use app\rss\RSSBitlyer;
use app\rss\RSSGenerator;
use app\rss\RSSInstantArticler;
use MBlogEntries;

class RSSCommand extends AbstractCrontabCommand
{
    private RSSGenerator $baseGenerator;
    private MBlogEntries $blogEntriesModel;
    private RSSBitlyer $bitlyer;

    public function __construct(RSSGenerator $generator, MBlogEntries $blogEntries, RSSBitlyer $bitlyer)
    {
        $this->baseGenerator = $generator;
        $this->blogEntriesModel = $blogEntries;
        $this->bitlyer = $bitlyer;
    }

    public function run(): void
    {
        $entries = $this->blogEntriesModel->getLastActive(5);

        $this->baseGenerator->title = 'Культурный туризм в России';
        $this->baseGenerator->link = GLOBAL_SITE_URL;
        $this->baseGenerator->email = 'abuse@culttourism.ru';
        $this->baseGenerator->description = 'Достопримечательности России и ближнего зарубежья: музеи, церкви и монастыри, памятники архитектуры';

        $generators = [
            'blog.xml' => new RSSAddUTM($this->bitlyer, 'feedburner'),
            'blog-dlvrit.xml' => new RSSAddUTM($this->bitlyer, 'dlvrit'),
            'blog-facebook.xml' => new RSSAddUTM(new RSSInstantArticler($this->bitlyer), 'facebook'),
            'blog-facebook-dev.xml' => new RSSAddUTM(new RSSInstantArticler($this->bitlyer), 'facebook'),
            'blog-facebook-ifttt.xml' => new RSSAddUTM(new RSSInstantArticler($this->bitlyer), 'facebook'),
            'blog-twitter.xml' => new RSSAddUTM($this->bitlyer, 'twitter'),
            'blog-telegram.xml' => new RSSAddUTM($this->bitlyer, 'telegram'),
            'blog-zen.xml' => new RSSAddUTM($this->bitlyer, 'zen'),
        ];

        foreach ($generators as $fileType => $generator) {
            /**
 * @var IRSSGenerator $generator 
*/
            $fileName = sprintf('%s/feed/%s', GLOBAL_DIR_DATA, $fileType); // имя RSS-файла
            $generator->url = sprintf('%sdata/feed/%s', GLOBAL_SITE_URL, $fileType); // URL файла

            file_put_contents($fileName, $generator->process($entries));
        }
    }
}
