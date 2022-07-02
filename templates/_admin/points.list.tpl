<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div class="points-menu-block">
    <a href="./links.php">Ссылки</a>
    <a href="./redirects.php">Редиректы</a>
</div>

<fieldset>
    <legend>Фильтр</legend>
    <form method="get">
        <table style="margin:0 auto; font-size: 80%;">
            <tr>
                <td>ID</td>
                <td><input type="text" name="oid" value="{$filter.oid}" style="width: 30px;" /></td>
                <td>Название</td>
                <td><input type="text" name="title" value="{$filter.title}" style="width: 200px;" /></td>
                <td>Страна</td>
                <td>
                    <select name="country" onchange="form.submit();">
                        <option value="-1">-- все --</option>
                        {foreach from=$refs.countries item=opt}
                        <option value="{$opt.id}" {if $opt.id == $filter.country}selected{/if}>{$opt.title}</option>
                        {/foreach}
                    </select>
                </td>
                <td>Регион</td>
                <td>
                    <select name="region" onchange="form.submit();">
                        <option value="-1">-- все --</option>
                        {foreach from=$refs.regions item=opt}
                        <option value="{$opt.id}" {if $opt.id == $filter.region}selected{/if}>{$opt.title}</option>
                        {/foreach}
                    </select>
                </td>
                <td>Город</td>
                <td>
                    <select name="city" onchange="form.submit();">
                        <option value="-1">-- все --</option>
                        {foreach from=$refs.cities item=opt}
                        <option value="{$opt.id}" {if $opt.id == $filter.city}selected{/if}>{$opt.title}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td>Тип</td>
                <td>
                    <select name="type" onchange="form.submit();">
                        <option value="-1">-- все --</option>
                        {foreach from=$refs.types item=opt}
                        <option value="{$opt.id}" {if $opt.id == $filter.type}selected{/if}>{$opt.title}</option>
                        {/foreach}
                    </select>
                </td>
                <td>Адрес</td>
                <td><input type="text" name="addr" value="{$filter.addr}" style="width: 200px;" /></td>
                <td>Телефон</td>
                <td><input type="text" name="phone" value="{$filter.phone}" style="width: 100px;" /></td>
                <td>Web</td>
                <td>
                    <input type="text" name="web" value="{$filter.web}" style="width: 100px;" />
                    <label>
                        Нет адреса
                        <input type="checkbox" name="noaddr" value="1" {if 1 == $filter.noaddr}checked{/if} />
                    </label>
                </td>
                <td>GPS</td>
                <td>
                    N<input type="text" name="gps_lat" value="{$filter.gps.lat}" style="width: 80px;" />
                    E<input type="text" name="gps_lon" value="{$filter.gps.lon}" style="width: 80px;" />
                </td>
            </tr>
            <tr>
                <td colspan="10" style="text-align: center;">
                    <input type="submit" value="Искать" />
                </td>
            </tr>
        </table>
    </form>
</fieldset>

<p>В выборке: {$points_cnt}</p>

{$pager}
<table style="background-color:teal; margin:10px auto; font-size: 80%;" cellpadding="3" cellspacing="1">
    <tr style="background-color:#DCDCDC;">
        <th>ID</th>
        <th>Страна</th>
        <th>Регион</th>
        <th colspan="2">Город</th>
        <th title="Тип">Т</th>
        <th>Название</th>
        <th title="Длина описания">Д</th>
        <th>Адрес</th>
        <th>Телефон</th>
        <th>Web</th>
        <th colspan="2">GPS</th>
    </tr>
    {foreach from = $points item=point}
    <tr style="background-color:#fff;">
        <td style="text-align: center;">
            <a href="{$point.url}/{$point.pt_slugline}.html" target="_blank">{$point.pt_id}</a>
        </td>
        <td>
            {$point.country_name}
        </td>
        <td>
            {$point.region_name}
        </td>
        <td>
            <a href="{$point.url}/" target="_blank" id="points-pctitle-{$point.pt_id}">{$point.pc_title}</a>
        </td>
        <td style="text-align: center;">
            <input type="button" class="points-moveto" data-oid="{$point.pt_id}" value=">" title="Переместить" />
        </td>
        <td>
            <img src="/img/points/x16/{$point.tp_icon}" alt="{$point.tp_short}" title="{$point.tp_name}">
        </td>
        <td>
            <a href="#" class="points-editprop" data-prop="pt_name" data-oid="{$point.pt_id}">
                {if $point.pt_deleted_at !== null}<s>{$point.pt_name}</s>{else}{$point.pt_name}{/if}
            </a>
            <div style="text-align: right;">
                <a href="#" class="points-editprop" data-prop="pt_slugline" data-oid="{$point.pt_id}" style="color:green;border: none;">
                    <i>{$point.pt_slugline}</i>
                </a>
            </div>
        </td>
        <td style="text-align: right; background-color:{if $point.descr_len < 100}#FF4500{elseif $point.descr_len < 200}#FFA583{elseif $point.descr_len < 500}#FFA583{else}#CCE4CC{/if};">
            {$point.descr_len}
        </td>
        <td>
            <a href="#" class="points-editprop" data-prop="pt_adress" data-oid="{$point.pt_id}">
                {$point.pt_adress}
            </a>
        </td>
        <td>
            {if $point.pt_phone != ''}
            <a href="#" class="points-editprop" data-prop="pt_phone" data-oid="{$point.pt_id}">
                {$point.pt_phone}
            </a>
            {else}
            <a href="#" class="points-editprop" data-prop="pt_phone" data-oid="{$point.pt_id}">
                +
            </a>
            {/if}
        </td>
        <td>
            {if $point.pt_website != ''}
            <a href="#" class="points-editprop" data-prop="pt_website" data-oid="{$point.pt_id}">
                {$point.pt_website}
            </a>
            &nbsp;
            <a href="{$point.pt_website}" target="_blank">
                <img src="/img/new-window.png" />
            </a>
            {else}
            <a href="#" class="points-editprop" data-prop="pt_website" data-oid="{$point.pt_id}">
                +
            </a>
            {/if}
        </td>
        <td style="font-size:80%;">
            <a href="/map/#center={$point.pt_longitude},{$point.pt_latitude}&zoom=14" target="_blank">
                {$point.pt_latitude}<br>{$point.pt_longitude}
            </a>
        </td>
        <td>
            <a href="http://toolserver.org/~geohack/geohack.php?language=ru&params={$point.pt_latitude}_N_{$point.pt_longitude}_E" target="_blank">
                >>>
            </a>
        </td>
    </tr>
    {/foreach}
</table>
{$pager}

<link rel="stylesheet" href="/css/admin/points.css" type="text/css" />
