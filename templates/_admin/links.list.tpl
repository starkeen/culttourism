<img class="pageicon" src="/img/admin/ico.a_refs.gif"/>
<h3>{$title}</h3>

<div>
    <fieldset>
        <legend>Фильтр</legend>
        <a class="" href="?">все</a>
        {foreach from=$statuses item=statusData}
            | <a class="{if $statusData.status == $status}selected-status{/if}" href="?status={$statusData.status}">{$statusData.status}</a> - <i>{$statusData.cnt}</i>
        {/foreach}
        <form method="get"></form>
    </fieldset>

    <p>&nbsp;</p>

    {$pager}

    <table class="commontable" cellspacing="1">
        <tr>
            <th title="Количество проверок">K</th>
            <th title="Тип точки">Т</th>
            <th>Точка</th>
            <th>URL</th>
            <th>Код</th>
            <th>Редирект</th>
        </tr>
        {foreach from=$links item=link}
            <tr>
                <td style="text-align: center;">
                    {$link.status_count}
                </td>
                <td>
                    <img src="/img/points/x16/{$link.tp_icon}"
                         alt="{$link.tp_short}"
                         title="{$link.tp_name}" />
                </td>
                <td>
                    <a href="{$link.url_point}" target="_blank">{$link.pt_name}</a>
                    <br/>
                    <a href="{$link.url_city}" style="font-style: italic" target="_blank">{$link.pc_title_unique}</a>
                </td>
                <td style="">
                    <a href="{$link.url}" target="_blank">{$link.url}</a>
                </td>
                <td class="{$link.status_class}" style="text-align: center;">
                    {$link.status}
                    <br/>
                    <span style="font-style: italic">{$link.content_kb|string_format:"%.1f"} kB</span>
                </td>
                <td>
                    <a href="{$link.redirect_url}" target="_blank">{$link.redirect_url}</a>
                    {if $link.process_redirect}
                        <br />
                        <span class="links-redirect-process" data-id="{$link.id}">использовать</span>
                    {/if}
                </td>
            </tr>
        {/foreach}
    </table>

    {$pager}
</div>

<link rel="stylesheet" href="/css/admin/links.css" type="text/css"/>
<script type="text/javascript" src="/js/admin/links.js" defer="defer"></script>
