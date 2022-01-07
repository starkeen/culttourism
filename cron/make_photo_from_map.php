<?php

use app\crontab\MakePhotoFromMapCommand;

$ph = new MPhotos($db);
$city = new MPageCities($db);

$command = new MakePhotoFromMapCommand($ph, $city);
$command->run();
