<?php

$cnt = Mailing::sendFromPool(10);
echo $cnt;