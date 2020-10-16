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
                    <a href="https://yandex.ru/yandsearch?text={$link.pt_name} {$link.pc_title_unique}" target="_blank">
                        <img src="/img/btn/btn.search.png" />
                    </a>
                    <br/>
                    <a href="{$link.url_city}" style="font-style: italic" target="_blank">{$link.pc_title_unique}</a>
                    <span class="point-address">{$link.pt_adress}</span>
                </td>
                <td class="table-cell-url">
                    <a href="{$link.url}" target="_blank" id="link-id-{$link.id}">{$link.url}</a>
                    <img class="links-delete-process" data-id="{$link.id}" src="/img/btn/btn.delete.png" />
                    <img class="links-edit-process" data-id="{$link.id}" src="/img/btn/btn.edit.png" />
                    <br />
                    <span class="links-content-title">{$link.content_title}</span>
                </td>
                <td class="{$link.status_class}" title="{$link.status_date}" style="text-align: center;">
                    {$link.status}
                    <br/>
                    <span style="font-style: italic">{$link.content_kb|string_format:"%.1f"} kB</span>
                </td>
                <td>
                    <a href="{$link.redirect_url}" target="_blank">{$link.redirect_url}</a>
                    {if $link.process_redirect}
                        <img class="links-redirect-process" data-id="{$link.id}" src="/img/btn/btn.tick.png" />
                    {/if}
                </td>
            </tr>
        {/foreach}
    </table>

    {$pager}
</div>

<link rel="stylesheet" href="/css/admin/links.css" type="text/css"/>
<script type="text/javascript" src="/js/admin/links.js" defer="defer"></script>
