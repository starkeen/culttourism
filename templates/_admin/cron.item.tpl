{literal}
<script>
    $(document).ready(function(){
        $('#btn_run').click(function(){
            {/literal}
            document.location.href="cron.php?crid={$task.cr_id}&act=run";
            {literal}
        });
        $('#btn_back').click(function(){
            document.location.href="cron.php";
        });
    });
</script>
<style>
    #btn_run {background-color:#FA8072}
</style>
{/literal}
<img class="pageicon" src="../img/admin/ico.a_logs.gif">
<h3>Задачи по расписанию</h3>
<form method="post">
    <table id="crontable" cellpadding="1" cellspacing="1">
        <tr>
            <th>Название задачи</th>
            <td>{$task.cr_title}</td>
        </tr>
        <tr>
            <th>Файл скрипта</th>
            <td>{$task.cr_script}</td>
        </tr>
        <tr>
            <th>Прошлая попытка</th>
            <td title="{$task.cr_lastresult}">{$task.date_lastatt}</td>
        </tr>
        <tr>
            <th>Прошлый запуск</th>
            <td>{$task.date_lastrun} ({$task.cr_lastexectime} сек.)</td>
        </tr>
        <tr>
            <th>Период запуска</th>
            <td><input type="text" name="period" value="{$task.cr_period}" style="width:80px;" /> [ЧЧ:ММ:СС]</td>
        </tr>
        <tr>
            <th>Следующий запуск</th>
            <td>
                <input type="text" name="next_day" value="{$task.day_next}" style="width:100px;" />
                <input type="text" name="next_time" value="{$task.time_next}" style="width:60px;" />
            </td>
        </tr>
        <tr>
            <td>
                <input type="hidden" name="ch_act" value="0" />
                <input id="ch_act" name="ch_act" class="input_elements" type="checkbox" value="1" {if $task.cr_active == 1}checked{/if} /> &mdash; <label for="ch_act">активность</label>
                <br>
                <input type="hidden" name="ch_run" value="0" />
                <input id="ch_run"  name="ch_run"class="input_elements" type="checkbox" value="1" {if $task.cr_isrun == 1}checked{/if} /> &mdash; <label for="ch_run">выполняется</label>
            </td>
            <td align="center">
                <input type="submit" value="Сохранить" />
                <input type="button" id="btn_run" value="Запустить" />
                <input type="button" id="btn_back" value="Вернуться" />
            </td>
        </tr>
    </table>
</form>