<img class="pageicon" src="../img/admin/ico.a_home.gif">
    <h3>Панель администрирования сайта</h3>
<table id="admenu_table" cellpadding="0" cellspacing="0">
    {foreach from=$adm_menu key=i item=admenuitem}
    <tr>
        <td><a href="{$admenuitem.link}"><img src="../img/admin/{$admenuitem.ico}"></a></td>
        <td><a href="{$admenuitem.link}">{$admenuitem.title}</a><td>
    </tr>
    {/foreach}
</table>


<div>
    Остаток лимита запросов к Яндексу: {$yandexSearchLimit}
</div>
<div>
    Остаток лимита запросов к Dadata: {$dadataLimit}
</div>
