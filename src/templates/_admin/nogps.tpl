<img class="pageicon" src="../img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<table style="background-color:teal; margin:10px;" cellpadding="3" cellspacing="1">
    <tr style="background-color:#DCDCDC;">
        <th>#</th>
        <th>Город</th>
        <th>Точек без<br/>координат</th>
        <th>Всего<br/>точек</th>
        <th>Процент</th>
    </tr>
    {foreach from=$points item=city}
    <tr style="background-color:#fff;">
        <td>{counter}</td>
        <td><a href="{$city.url}/">{$city.pc_title}</a></td>
        <td align="center">{$city.cnt}</td>
        <td align="center">{$city.cnt_all}</td>
        <td align="center">
            {if $city.p>=50}<strong>{/if}
                {$city.p}
                {if $city.p>=50}</strong>{/if}
        </td>
    </tr>
    {/foreach}
</table>