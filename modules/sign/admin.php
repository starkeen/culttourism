<?php
function getAdminArea($smarty=null) {
    return $smarty->fetch(_DIR_TEMPLATES.'/sign/admin.sm.html');;
}
?>