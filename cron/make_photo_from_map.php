<?php

$ph = new MPhotos($db);
$cities = $ph->getCityPagesWithoutPhotos();
