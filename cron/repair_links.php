<?php

$c = new MPageCities($db);
$p = new MPagePoints($db);
$l = new MLists($db);
$b = new MBlogEntries($db);

$c->repairLinksAbsRel();
$p->repairLinksAbsRel();
$l->repairLinksAbsRel();
$b->repairLinksAbsRel();
