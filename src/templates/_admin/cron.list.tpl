<img class="pageicon" src="../img/admin/ico.a_logs.gif">
<h3>Задачи по расписанию</h3>

<table id="crontable" cellpadding="1" cellspacing="1" style="margin:0 auto;">
    <tr>
        <th>#</th>
        <th>Название задачи</th>
        <th>Активность</th>
        <th>Прошлая попытка</th>
        <th>Прошлый запуск</th>
        <th>Период</th>
        <th>Следующий запуск</th>
        <th>Время исполнения</th>
    </tr>
    {foreach from=$crons item=task}
    <tr>
        <td align="right" {if $task.cr_isrun == 1}style="background-color:red;"{/if}>{counter}</td>
        <td><a href="?crid={$task.cr_id}&act=edit" title="редактировать">{$task.cr_title}</a></td>
        <td align="center">
            {if $task.cr_active == 1}<b>ВКЛ</b>{else}откл{/if}
            <a style="color:red;" href="?crid={$task.cr_id}&act=run" title="запустить">[run]</a>
            <a style="color:green;" href="?crid={$task.cr_id}&act=stop" title="остановить">[stop]</a>
        </td>
        <td>{$task.date_lastatt}</td>
        <td>{$task.date_lastrun}</td>
        <td>{$task.cr_period}</td>
        <td>{$task.date_next}</td>
        <td title="{$task.cr_lastresult}">{$task.cr_lastexectime}</td>
    </tr>
    {/foreach}
</table>