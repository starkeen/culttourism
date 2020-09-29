<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div>
    <fieldset>
        <legend>Фильтр</legend>
        <form method="get"></form>
    </fieldset>

    {$pager}

    <table class="commontable" cellspacing="1">
        <tr>
            <th>Точка</th>
            <th>URL</th>
            <th>Код</th>
        </tr>
        {foreach from=$links item=link}
            <tr>
                <td>
                    <a href="{$link.url_point}" target="_blank">{$link.pt_name}</a>
                    <br/>
                    <a href="{$link.url_city}" style="font-style: italic" target="_blank">{$link.pc_title_unique}</a>
                </td>
                <td style="">
                    <a href="{$link.url}" target="_blank">{$link.url}</a>
                </td>
                <td style="text-align: center;">
                    {$link.status}
                    <br />
                    <span style="font-style: italic">{$link.content_kb|string_format:"%d"} kB</span>
                </td>
            </tr>
        {/foreach}
    </table>

    {$pager}
</div>
