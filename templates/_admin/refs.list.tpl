<img class="pageicon" src="/img/admin/ico.a_refs.gif">
<h3>Настройки сайта</h3>
<ul>
    {foreach from=$reflist item=ref key=rid}
    <li>
        <a href="?rid={$rid}">
            {if $rid==1}<b>{$ref}</b>{else}{$ref}{/if}
        </a>
    </li>
    {/foreach}
</ul>

<hr />

<ul>
    <li><a href="cron.php">Задачи по расписанию</a></li>
    <li><a href="log_error.php">Журнал ошибок</a></li>
</ul>
