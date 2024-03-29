{literal}
<script>
    $(document).ready(function() {
        $('#btn_delitem').click(function(){
            if (!confirm('Действительно удалить запись?')) return false;
        });
    });
</script>
{/literal}
<img class="pageicon" src="/img/admin/ico.a_modules.gif">
<h3>Управление записями в блоге</h3>
<form method="post">
    <table id="usertable" cellspacing="1" width="80%">
        {if $is_edit}
        <tr>
            <th>ID</th>
            <td>{$blogitem.br_id}</td>
            <th>Дата</th>
            <td>{$blogitem.bg_datex}</td>
        </tr>
        {/if}
        <tr>
            <th style="width: 10%;">Заголовок</th>
            <td colspan="3"><input type="text" name="br_title" value="{$blogitem.br_title}" style="width: 100%;"></td>
        </tr>
        <tr><th colspan="4" align="center" style="text-align:center">Текст записи</th></tr>
        <tr><td colspan="4" align="center">{$blogitem.br_texteditor}</td></tr>
        <tr>
            <th>Активность</th>
            <td>
                <input type="hidden" name="br_active" value="0">
                <input type="checkbox" name="br_active" value="1" {if $blogitem.br_active==1}checked{/if} />
            </td>
            <th>URL</th>
            <td>blog/{$blogitem.bg_datelink}/<input type="text" name="br_url" value="{$blogitem.br_url}" style="width: 70%;"></td>
        </tr>
        <tr>
            <td colspan="4" align="center">
                <input type="submit" value="Сохранить" name="to_save">
                {if $is_edit}<input type="submit" value="Удалить" name="to_del" id="btn_delitem">{/if}
                <input type="submit" value="Вернуться" name="to_ret">
            </td>
        </tr>
    </table>
</form>