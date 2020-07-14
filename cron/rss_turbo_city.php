<?php

use app\crontab\RssTurboCityCommand;

$fileName = sprintf('%s/feed/%s', _DIR_DATA, 'turbo-city.xml');

$cityModel = new MPageCities($db);

$command = new RssTurboCityCommand($cityModel);
$command->run($fileName);
