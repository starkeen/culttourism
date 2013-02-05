<?php
function getAdminArea($smarty=null) {
    return $smarty->fetch(_DIR_TEMPLATES.'/blog/admin.sm.html');;
}
?>