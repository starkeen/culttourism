<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<table class="commontable" style="float:left;">
    <tr>
        <th>Регион</th>
        {foreach from=$matrix.types key=tid item=t}
        <th>
            <a href="?type={$tid}"><img src="/img/points/x16/{$t.icon|default:'star.png'}"/></a>
        </th>
        {/foreach}
    </tr>
    {foreach from=$matrix.counts key=pcid item=c}
    <tr>
        <td style="text-align:left;">
            <a href="?pcid={$pcid}">{$c.title}</a>
        </td>
        {foreach from=$matrix.types key=tid item=t}
        <td style="text-align:center;">
            <a href="?pcid={$pcid}&type={$tid}">{$c.types.$tid}</a>
        </td>
        {/foreach}
    </tr>
    {/foreach}
    <tr>
        <th>Итого:</th>
        {foreach from=$matrix.types key=tid item=t}
        <th>{$t.total}</th>
        {/foreach}
    </tr>
</table>

<form method="get" action="addpoints.php">
    <table class="commontable pointadding-list">
        <tr>
            <th>№ п/п</th>
            <th>Дата</th>
            <th>Тип</th>
            <th>Регион</th>
            <th>Страница</th>
            <th>Название</th>
            <th>L</th>
            <th>GPS</th>
            <th>Статус</th>
            <th>&nbsp;</th>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>
                <select name="type" style="width:50px;" onchange="submit();">
                    <option value="0">--все--</option>
                    <option value="-1" {if $filter.type == -1}selected{/if}>-нет-</option>
                    {foreach from=$ref_pt item=pt}
                    <option value="{$pt.tp_id}" {if $pt.tp_id==$filter.type}selected{/if}>{$pt.tp_short}</option>
                    {/foreach}
                </select>
            </td>
            <td>&nbsp;</td>
            <td>
                <select name="pcid" style="width:100px;" onchange="submit();">
                    <option value="0">--все--</option>
                    {foreach from=$ref_pc item=pg}
                    <option value="{$pg.id}" {if $pg.id==$filter.pcid}selected{/if}>{$pg.title}</option>
                    {/foreach}
                </select>
            </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>
                <select name="gps" style="width:50px;" onchange="submit();">
                    <option value="0">--все--</option>
                    <option value="-1" {if -1==$filter.gps}selected{/if}>нет</option>
                    <option value="1" {if 1==$filter.gps}selected{/if}>есть</option>
                </select>
            </td>
            <td>
                <select name="state" style="width:80px;" onchange="submit();">
                    <option value="0">--все--</option>
                    {foreach from=$ref_st item=st}
                    <option value="{$st.uv_id}" {if $st.uv_id==$filter.state}selected{/if}>{$st.uv_title}</option>
                    {/foreach}
                </select>
            </td>
            <td>&nbsp;</td>
        </tr>
        {foreach from=$list item=req}
        <tr>
            <td class="{if $req.dc_id}m_checked{/if}">{counter}</td>
            <td>{$req.cp_date}</td>
            <td title="{$req.type_title}" class="m_center"><img src="/img/points/x16/{$req.type_icon|default:'star.png'}"/></td>
            <td>{$req.cp_city}</td>
            <td><a href="{$req.page_url}" target="_blank">{$req.page_title}</a></td>
            <td>
                {$req.cp_title}
                <br />
                <span>{$req.cp_addr}</span>
            </td>
            <td style="text-align: right; background-color:{if $req.text_len < 100}#FF4500{elseif $req.text_len < 200}#FFA583{elseif $req.text_len < 500}#FFA583{else}#CCE4CC{/if};">
                {$req.text_len}
            </td>
            <td style="text-align: center;">{if $req.cp_latitude>0 && $req.cp_longitude>0}есть{else}нет{/if}</td>
            <td>{$req.state_title}</td>
            <td><a href="?id={$req.cp_id}"><img src="/img/btn/btn.tick.png"</a></td>
        </tr>
        {/foreach}
    </table>
</form>
