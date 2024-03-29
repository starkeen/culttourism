<script type="text/javascript" src="/js/editor.js" defer="defer"></script>
<input type="hidden" id="pc_id" value="{$city.pc_id}" />
<input type="text" id="pc_title_edit" class="hiddenedit h1" value="{$city.pc_title}" />
<input type="hidden" id="pc_title_hidd" value="{$city.pc_title}" />
<div class="formhandler" id="pc_title_handler">
    <input type="button" value="сохранить" class="dosave" />
    <input type="button" value="отменить" class="doesc" />
</div>

<div class="CityPath">{$city.pc_pagepath}</div>

{if $city.pc_latitude && $city.pc_longitude}
<div id="map_container">
    <div id="city_map"></div>
    <div id="city_types">
        {foreach from=$ptypes item=ptype}
        <a href="#" title="{$ptype.tp_name}"><img src="/img/points/32/{$ptype.tp_icon}" alt="{$ptype.tp_name}" /></a>
        {/foreach}
    </div>
    <a id="city_map_link" href="/map/#center={$city.pc_longitude},{$city.pc_latitude}&zoom={$city.pc_zoom+1}" title="Большая карта достопримечательностей {$city.pc_inwheretext}">на весь экран</a>
</div>
{/if}

<div id="citytext">
    <div id="city_float">
        <div class="city_weather" data-lat="{$city.pc_latitude}" data-lon="{$city.pc_longitude}" title="погодные условия">
            <img src="/img/preloader/horizontal.gif" title="идет загрузка погоды" alt="идет загрузка" style="vertical-align: middle" />
        </div>
        <div class="city_metadata" title="информация" data-pcid="{$city.pc_id}">
            <table>
                {foreach from=$city.metas item=meta}
                <tr>
                    <th>{$meta.cf_title}:</th>
                    <td>{$meta.cd_value}</td>
                </tr>
                {/foreach}
            </table>
        </div>
    </div>
    {if $city.pc_announcement}<div id="city_announcement">{$city.pc_announcement}</div>{/if}
    <div id="pc_text_hidd" class="hiddenedit">{$city.pc_text}</div>
    <textarea id="pc_text_edit" class="hiddenedit_active" style="width:100%;display:none;"></textarea>
    <div class="formhandler" id="pc_text_handler">
        <input type="button" value="сохранить" class="dosave" />
        <input type="button" value="отменить" class="doesc" />
    </div>
</div>

{if $city.region_in}
<div id="city_inners" class="city_nearmenu">
    {$city.pc_title} включает в себя
    <ul class="menu_common">
        {foreach from=$city.region_in item=incity}
        <li><a href="{$incity.url}/" title="достопримечательности {$incity.where}">{$incity.title}</a></li>
        {/foreach}
    </ul>
</div>
{/if}
{if $city.region_near}
<div id="city_nears" class="city_nearmenu">
    Рядом:
    <ul class="menu_common">
        {foreach from=$city.region_near item=incity}
        <li><a href="{$incity.url}/" title="достопримечательности {$incity.where}">{$incity.title}</a></li>
        {/foreach}
    </ul>
</div>
{/if}

<div style="text-align:right;"><a href="/city/detail/?city_id={$city.pc_id}"><b>ключевые слова</b></a></div>

<div id="citypoints">

    <h2>Достопримечательности {$city.pc_inwheretext}</h2>

    <ul id="menu_type1" class="points_selector">
        <li>
            <a class="points_selector_active typefilterlink" href="#type_all" title="показать всё">
                <img class="seltype_icon" src="/img/points/x32/star.png" alt="показать всё" />
                <span class="seltype_capt">всё</span>
            </a>
        </li>
        {foreach from=$types_select.1 item=seltype key=typeid}
        <li>
            <a class="points_selector_inactive typefilterlink" href="#type_{$typeid}" id="type_selector_{$typeid}" title="{$seltype.full}">
                <img class="seltype_icon" src="/img/points/x32/{$seltype.icon}" alt="{$seltype.full}" />
                <span class="seltype_capt">{$seltype.short}</span>
            </a>
        </li>
        {/foreach}
    </ul>

    <table id="whatseelist" class="citypoints_list">
        {foreach from=$points_sight item=item}
        <tr class="obj_type_{$item.pt_type_id}">
            <td class="td_obj_icon{if $item.pt_is_best == 1} obj_best{/if}">
                {if $item.pt_type_id != 0}
                <img class="point_typer" id="type_{$item.pt_id}" src="/img/points/x32/{$item.tp_icon}" alt="{$item.tp_name}" />
                {else}
                <img class="point_typer" id="type_{$item.pt_id}" src="/img/points/x32/star.png" alt="другое" />
                {/if}
            </td>
            <td>
                <a href="{$item.url_canonical}" id="object_id_{$item.pt_id}" class="objlink" title="подробно: {$item.pt_name}" {if $item.pt_deleted_at !== null}style="color:#777;"{/if}>
                   {$item.pt_name}
            </a>
        </td>
        <td>
            {if $item.gps_dec}<a href="#" id="gps_{$item.pt_id}" class="point_latlon">{$item.gps_dec}</a>{else}<a href="#" id="gps_{$item.pt_id}" class="point_latlon">указать</a>{/if}
        </td>
        <td>
            <img class="point_deleter" id="del_{$item.pt_id}" src="/img/btn/ico.delete.gif" />
        </td>
    </tr>
    <tr>
        <td colspan="4"><span class="point_short">{$item.short}</span></td>
    </tr>
    {/foreach}
</table>
<button id="do_add_point">добавить</button>


<h2>Полезная информация</h2>

<ul id="menu_type2" class="points_selector">
    <li>
        <a class="points_selector_active typefilterlink" href="#type_all" title="показать всё">
            <img class="seltype_icon" src="/img/points/x32/star.png" alt="показать всё" />
            <span class="seltype_capt">всё</span>
        </a>
    </li>
    {foreach from=$types_select.0 item=seltype key=typeid}
    <li>
        <a class="points_selector_inactive typefilterlink" href="#type_{$typeid}" id="type_selector_{$typeid}" title="{$seltype.full}">
            <img class="seltype_icon" src="/img/points/x32/{$seltype.icon}" alt="{$seltype.full}" />
            <span class="seltype_capt">{$seltype.short}</span>
        </a>
    </li>
    {/foreach}
</ul>

<table id="whatservlist" class="citypoints_list">
    {foreach from=$points_servo item=item}
    <tr class="obj_type_{$item.pt_type_id}">
        <td>{if $item.pt_type_id != 0}
            <img class="point_typer" id="type_{$item.pt_id}" src="/img/points/x32/{$item.tp_icon}" alt="{$item.tp_name}" />
            {else}
            <img class="point_typer" id="type_{$item.pt_id}" src="/img/points/x32/star.png" alt="другое" />
            {/if}
        </td>
        <td>
            <a href="{$item.url_canonical}" id="object_id_{$item.pt_id}" class="objlink" title="подробно: {$item.pt_name}" {if $item.pt_deleted_at !== null}style="color:#777;"{/if}>
               {$item.pt_name}
        </a>
    </td>
    <td>
        {if $item.gps_dec}<a href="#" id="gps_{$item.pt_id}" class="point_latlon">{$item.gps_dec}</a>{else}<a href="#" id="gps_{$item.pt_id}" class="point_latlon">указать</a>{/if}
    </td>
    <td>
        <img class="point_deleter" id="del_{$item.pt_id}" src="/img/btn/ico.delete.gif" />
    </td>
</tr>
<tr>
    <td colspan="4"><span class="point_short">{$item.short}</span></td>
</tr>
{/foreach}
</table>
</div>

{if $city.pc_latitude && $city.pc_longitude}
<input type="hidden" id="mapcity_pc_id" value="{$city.pc_id}" />
<input type="hidden" id="mapcity_pc_latitude" value="{$city.pc_latitude}" />
<input type="hidden" id="mapcity_pc_longitude" value="{$city.pc_longitude}" />
<input type="hidden" id="mapcity_pc_zoom" value="{$city.pc_zoom}" />
<input type="hidden" id="mapcity_pc_osmid" value="{$city.pc_osm_id}" />
<input type="hidden" id="mapcity_pc_country" value="{$city.pc_country_code}" />
{/if}

<div id="yandex_ad_city" class="CommonYandexAdverts"></div>
<div id="yandex_context_city"></div>
