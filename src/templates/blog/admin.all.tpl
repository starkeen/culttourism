{literal}
<script>
    $(document).ready(function() {
        $('#btn_addrecord').click(function(){
            document.location = 'blog.php?act=add';
        });
    });
</script>
{/literal}

<img class="pageicon" src="/img/admin/ico.a_modules.gif">
<h3>Управление записями в блоге</h3>
{if $bloglist}
<table style="background-color:teal; margin:10px;" cellpadding="3" cellspacing="1">
    <tr style="background-color:#DCDCDC;">
        <th>Дата</th>
        <th>Заголовок</th>
        <th>Адрес</th>
        <th>Автор</th>
        <th>Активность</th>
        <th>Редактировать</th>
    </tr>
    {foreach from=$bloglist item=entry key=bid}
    <tr style="background-color:#ffffff;">
        <td>{$entry.bg_datex}</td>
        <td>{$entry.br_title}</td>
        <td><a href="/blog/{$entry.br_link}" target="_blanc">/{$entry.br_link}</a></td>
        <td>{$entry.us_name}</td>
        <td align="center">{if $entry.br_active}<b>ВКЛ</b>{else}откл{/if}</td>
        <td align="center"><a href="?id={$bid}" title="Редактировать"><img src="/img/admin/ico.edit.gif" alt="Редактировать"></a></td>
    </tr>
    {/foreach}
</table>
{else}
Записей в блоге еще нет. Приступим?
{/if}
&nbsp;<input type="button" value="Добавить запись" id="btn_addrecord">