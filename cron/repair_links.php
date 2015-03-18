<?php

$c = new Cities($db);
$p = new Points($db);
$l = new Lists($db);
$b = new MBlogEntries($db);

$c->repairLinksAbsRel();
$p->repairLinksAbsRel();
$l->repairLinksAbsRel();
$b->repairLinksAbsRel();
