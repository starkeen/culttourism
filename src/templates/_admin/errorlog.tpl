{literal}
<script>
    $(document).ready(function(){
        $('#clear_btn').click(function(){
            if (!confirm('Удаление необратимо! Очистить таблицу?')) return false;
        });
    });
</script>
{/literal}
<img class="pageicon" src="../img/admin/ico.a_logs.gif">
<h3>Журнал ошибок</h3>
<table id="erlogtbl" cellpadding="1" cellspacing="1">
    <tr>
        <th>#</th>
        <th>Дата и время</th>
        <th>Тип ошибки</th>
        <th>Запрошенный URL</th>
        <th>Откуда пришел</th>
        <th>Адрес<br>пользователя</th>
        <th>Браузер пользователя</th>
    </tr>
    {foreach from=$records item=rec}
    <tr>
        <td align="right">{$rec.le_id}</td>
        <td>{$rec.le_date}</td>
        <td align="center">{$rec.le_type}</td>
        <td>{$rec.le_url}</td>
        <td>{if $rec.le_referer=='undefined'}<span style="color:silver;"><i>undefined</i></span>{else}<a href="{$rec.le_referer}">{$rec.le_referer}</a>{/if}</td>
        <td>{$rec.le_ip}</td>
        <td>{$rec.le_browser}</td>
    </tr>
    {/foreach}
    <tr>
        <td colspan="7" align="right">
            <form method="post">
                <input id="clear_btn" name="clear_btn" type="submit" value="Очистить таблицу">
            </form>
        </td>
    </tr>
</table>