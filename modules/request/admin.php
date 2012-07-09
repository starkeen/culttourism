<?php
function getAdminArea($smarty=null) {
    return $smarty->fetch(_DIR_TEMPLATES.'/request/admin.sm.html');;
}
?>