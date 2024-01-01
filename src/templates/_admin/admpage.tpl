<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>{$title}</title>
        <meta name="robots" content="noindex, nofollow" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="stylesheet" href="/css/admin.css" type="text/css" />
        <script type="text/javascript" src="/addons/jquery/jquery.2.1.3.min.js"></script>
        <script type="text/javascript" src="/addons/jquery/jquery-migrate-1.2.1.min.js"></script>
        <script type="text/javascript" src="/addons/autocomplete/jquery.autocomplete.min.js"></script>
        <script type="text/javascript" src="/addons/ckeditor/ckeditor.js" defer="defer"></script>
        <script type="text/javascript" src="/addons/ckeditor/adapters/jquery.js" defer="defer"></script>
        <script type="text/javascript" src="/js/admin.js"></script>
    </head>
    <body>
        <div id="admheader">
            <p id="mainadmin">
                <a href="/_admin"><b>Административная часть сайта</b></a>&nbsp;
                <a href="/">{$site_url}</a>
            </p>
            <p id="adm_username">{if $adm_user}вы вошли как <b>{$adm_user}</b>{else}необходима авторизация{/if}</p>
            <ul id="admmainmenu">
                {foreach from=$adm_menu key=i item=admenuitem}
                <li><a href="{$admenuitem.link}">{$admenuitem.title}</a></li>
                {/foreach}
                <li><a href="login.php?out"><b>Выйти</b></a></li>
            </ul>
        </div>
        <div id="admcontent">
            {$content}
        </div>
    </body>
</html>
