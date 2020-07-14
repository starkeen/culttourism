<?php

use app\crontab\RssTurboBlogCommand;

$blogModel = new MBlogEntries($db);

$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-blog.xml');

$command = new RssTurboBlogCommand($blogModel);
$command->run($fileName);
