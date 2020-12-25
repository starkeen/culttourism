<div id="object_container">
    <table id="objects_list" cellspacing="0">
        {foreach from=$points item=point}
        <tr>
            <td colspan="3"><hr/></td>
        </tr>
        <tr>
            <td rowspan="2" class="objects_icon"><img src="/img/points/x32/{$point.tp_icon}" alt="{$point.tp_name}" /></td>
            <td>
                <a class="objects_title" id="ptitle_{$point.pt_id}" href="/api/2/?id={$point.pt_id}&latlon={$point.pt_latitude},{$point.pt_longitude}">{$point.pt_name}</a>
                <span class="objects_dist">~{$point.pt_dist} м</span>
            </td>
            <td rowspan="2" class="objects_addrs"><div>{$point.pt_adress}</div></td>
        </tr>
        <tr>
            <td class="objects_descr">{if $point.pt_short!=''}{$point.pt_short}{else}&nbsp;{/if}</td>
        </tr>
        {/foreach}
    </table>
    <div id="object_card" class="toggled"></div>
</div>