<?php

$c = new Cities($db);
$p = new Points($db);
$l = new Lists($db);

$c->repairLinksAbsRel();
$p->repairLinksAbsRel();
$l->repairLinksAbsRel();
