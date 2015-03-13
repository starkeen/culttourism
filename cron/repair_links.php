<?php

$c = new Cities($db);
$p = new Points($db);

$c->repairLinksAbsRel();
$p->repairLinksAbsRel();
