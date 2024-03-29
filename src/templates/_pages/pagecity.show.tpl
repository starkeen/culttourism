<h1>{$city.pc_title}</h1>

<div class="CityPath">{$city.pc_pagepath}</div>

{if $city.pc_latitude && $city.pc_longitude}
<div id="map_container">
    <div id="city_map" style="{if $page_image}background-image: url({$page_image});{/if}"></div>
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
    {if $city.pc_announcement}
    <div id="city_announcement">{$city.pc_announcement}</div>
    {/if}
    {$city.pc_text}
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
    <strong>Рядом:</strong>
    <ul class="menu_common">
        {foreach from=$city.region_near item=incity}
        <li><a href="{$incity.url}/" title="достопримечательности {$incity.where}">{$incity.title}</a></li>
        {/foreach}
    </ul>
</div>
{/if}

<hr />

<div id="citypoints">
    {if $points_sight}
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
                <a href="{$item.url_canonical}" title="подробно: {$item.pt_name}">
                    <img class="obj_icon_img" src="/img/points/x32/{$item.tp_icon}" alt="{$item.tp_name}: {$item.pt_name}" />
                </a>
                {else}<img class="obj_icon_img" src="/img/points/x32/star.png" alt="другое" />{/if}
                <span class="hidden_overflow_bottom"></span>
            </td>
            <td>
                <a href="{$item.url_canonical}" class="objlink" title="подробно: {$item.pt_name}"><strong>{$item.pt_name}</strong></a>
                <a href="{$item.url_canonical}" title="{$item.pt_name}, открыть в новом окне" rel="external">
                    <img src="/img/new-window.png" alt=">" class="link_external" />
                </a>
                <br />
                <span class="point_short">{$item.short}</span>
                <span class="hidden_overflow_bottom"></span>
            </td>
            <td class="obj_additional">
                {if $item.gps_dec}<br /><span class="point_gpsshort">{$item.gps_dec}&nbsp;</span>{/if}
                <span class="hidden_overflow_bottom"></span>
            </td>
        </tr>
        {/foreach}
    </table>

    <p></p>
    <div id="yandex_context_city"></div>
    <p></p>

    {if $points_servo}
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
            <td class="td_obj_icon">
                {if $item.pt_type_id != 0}
                <a href="{$item.url_canonical}" title="подробно: {$item.pt_name}">
                    <img src="/img/points/x32/{$item.tp_icon}" alt="{$item.tp_name}: {$item.pt_name}" />
                </a>
                {else}
                <img src="/img/points/x32/star.png" alt="другое" />
                {/if}
                <span class="hidden_overflow_bottom"></span>
            </td>
            <td>
                <a href="{$item.url_canonical}" class="objlink" title="подробно: {$item.pt_name}"><strong>{$item.pt_name}</strong></a>
                <a href="{$item.url_canonical}" title="{$item.pt_name}, открыть в новом окне" rel="external"><img src="/img/new-window.png" alt=">" class="link_external" /></a>
                <br />
                <span class="point_short">{$item.short}</span>
                <span class="hidden_overflow_bottom"></span>
            </td>
            <td class="obj_additional">
                {if $item.gps_dec}<br /><span class="point_gpsshort">{$item.gps_dec}</span>{/if}
                <span class="hidden_overflow_bottom"></span>
            </td>
        </tr>
        {/foreach}
    </table>
    {/if}
    {else}
    {if !in_array($city.pc_id, array(120,123,124))}
    <p>Как видите, о достопримечательностях пока информации нет. Если хотите помочь ресурсу, <a href="/feedback/">напишите нам</a>.</p>
    {/if}
    {/if}
</div>

{if $city.pc_latitude && $city.pc_longitude}
<input type="hidden" id="mapcity_pc_id" value="{$city.pc_id}" />
<input type="hidden" id="mapcity_pc_latitude" value="{$city.pc_latitude}" />
<input type="hidden" id="mapcity_pc_longitude" value="{$city.pc_longitude}" />
<input type="hidden" id="mapcity_pc_zoom" value="{$city.pc_zoom}" />
<input type="hidden" id="mapcity_pc_osmid" value="{$city.pc_osm_id}" />
<input type="hidden" id="mapcity_pc_country" value="{$city.pc_country_code}" />
{/if}

<div id="yandex_sharer">
    <script type="text/javascript" src="https://yastatic.net/share/share.js" charset="utf-8" defer="defer"></script><div class="yashare-auto-init" data-yashareL10n="ru" data-yashareType="small" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki,moimir" data-yashareTheme="counter"></div>
</div>

<div id="yandex_ad_city" class="CommonYandexAdverts"></div>
