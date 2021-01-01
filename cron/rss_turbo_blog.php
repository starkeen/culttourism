<?php

use app\crontab\RssTurboBlogCommand;

$blogModel = new MBlogEntries($db);

$fileName = sprintf('%s/feed/%s', GLOBAL_DIR_DATA, 'turbo-blog.xml');

$command = new RssTurboBlogCommand($blogModel);
$command->run($fileName);
