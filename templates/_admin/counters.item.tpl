{literal}
<script>
    $(document).ready(function() {
        $('#btn_delcnt').click(function() {
            if (!confirm('Действительно удалить из базы?'))
                return false;
        });
    });
</script>
{/literal}
<img class="pageicon" src="/img/admin/ico.a_counters.gif">
<h3>
    Редактирование счетчика
</h3>
<form method="post">
    <table id="usertable" cellspacing="1">
        <tr>
            <th>Наименование</th>
            <td><input type="text" name="cnt_title" value="{$cnt.cnt_title}" style="width: 250px;"></td>
        </tr>
        <tr>
            <th colspan="2" style="text-align:center;">Текст счетчика</th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea name="cnt_text" style="width:400px;height:200px;">{$cnt.cnt_text}</textarea>
            </td>
        </tr>
        {if !$add}
        <tr>
            <th>Дата установки</th>
            <td>{$cnt.cnt_datefrom}</td>
        </tr>
        {/if}
        <tr>
            <th>Активность</th>
            <td><input type="hidden" name="cnt_active" value="0">
                <input type="checkbox" id="cnt_active" name="cnt_active" value="1" {if $cnt.cnt_active == 1}checked{/if}>
                       <label for="cnt_active">- вкл показ на сайте</label>
            </td>
        </tr>
        <tr>
            <th>Порядок</th>
            <td><input type="text" name="cnt_sort" value="{$cnt.cnt_sort}" style="width: 50px;"></td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <input type="submit" value="Сохранить" name="to_save">
                {if $is_edit}<input type="submit" value="Удалить" name="to_del" id="btn_delcnt">{/if}
                <input type="submit" value="Вернуться" name="to_ret">
            </td>
        </tr>
    </table>
</form>