<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<table class="commontable" cellspacing="1">
    <tr>
        <th>ID</th>
        <th>Наименование</th>
        <th>URL-часть</th>
        <th>Текст</th>
        <th>Элементов</th>
        <th>Порядок</th>
        <th>Активность</th>
        <th>&nbsp;</th>
    </tr>
    {foreach from=$lists item=list}
    <tr>
        <td style="text-align: center;">{$list.ls_id}</td>
        <td>{$list.ls_title}</td>
        <td><a href="/list/{$list.ls_slugline}.html" target="_blank">{$list.ls_slugline}</a></td>
        <td style="text-align: center; background-color: {if $list.len_text < 100}#FF4500{elseif $list.len_text < 200}#FFA583{elseif $list.len_text < 500}#FFA583{else}#CCE4CC{/if};">{$list.len_text}</td>
        <td style="text-align: center;">{$list.cnt}</td>
        <td style="text-align: center;">{$list.ls_order}</td>
        <td style="text-align: center;">{if $list.ls_active}<b>ВКЛ</b>{else}откл{/if}</td>
        <td><a href="?id={$list.ls_id}"><img src="/img/admin/ico.edit.gif" /></a></td>
    </tr>
    {/foreach}
</table>