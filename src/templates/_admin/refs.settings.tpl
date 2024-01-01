{literal}
<style>
    input {
        border-width:1px;
        background-color: #fff;
    }
    input[type=text] {
        width: 400px;
    }
    td {
        vertical-align: top;
    }
</style>
<script type="text/javascript">
    $(document).ready(function(){
        $('#btn_back').click(function(){
            document.location.href = 'settings.php';
        });
    });
</script>
{/literal}
<img class="pageicon" src="../img/admin/ico.a_refs.gif">
<h3>Общие настройки сайта</h3>
<form method="post">
    <table>
        <tr>
            <th>#</th>
            <th>Параметр</th>
            <th>Значение</th>
            <th>Описание</th>
        </tr>
        {foreach from=$setts item=param key=id}
        <tr>
            <td>{counter}.</td>
            <td>{$param.sp_title}</td>
            <td><input type="text" name="param[{$id}]" value="{$param.sp_value}"></td>
            <td style="font-size:80%;">{$param.sp_whatis}</td>
        </tr>
        {/foreach}
        <tr>
            <td colspan="4" align="center">
                <input type="submit" value="Сохранить">
                <input type="button" id="btn_back" value="Вернуться">
            </td>
        </tr>
    </table>
</form>