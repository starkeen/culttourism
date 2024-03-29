<h1>{$object.pt_name}</h1>
<div class="CityPath">{$city.pc_pagepath}</div>

{if $page_image}
<div class="PageObjectPhoto">
    <img class="PageObjectPhotoImage"
         src="{$page_image}"
         alt="{$object.pt_name} ({$city.pc_title})"
         title="{$object.pt_name}" />
</div>
{else}
    {literal}<style>.PageObjectDescription{margin-right:auto;}</style>{/literal}
{/if}

<div class="PageObjectDescription">
    <img class="PageObjectIcon"
         alt="{$object.tp_name}"
         title="{$object.tp_name}"
         src="/img/points/x32/{$object.tp_icon}" />
    {$object.pt_description}

    {if !empty($lists)}
    <p class="PageObjectListsTitle">Смотрите также:</p>
    <ul class="PageObjectListsBlock">
        {foreach from=$lists item=list}
        <li><a href="/list/{$list.ls_slugline}.html">{$list.ls_title}</a></li>
        {/foreach}
    </ul>
    {/if}

    <p><a href="."
          class="PageObjectCityLink"
          title="перейти к достопримечательностям {$city.pc_inwheretext}"
        >&larr; достопримечательности {$city.pc_inwheretext}</a>
    </p>
</div>

{if $object.pt_latitude && $object.pt_longitude}
<div class="PageObjectMapContainer">
    <div id="PageObjectMap"></div>
    <div class="PageObjectMapGPS">GPS-координаты: {$object.gps_dec}</div>
</div>
{/if}

{if $object.pt_adress || $object.pt_worktime || $object.pt_website || $object.pt_email || $object.pt_phone}
<div class="PageObjectContactsBlock">
    <div id="object_contacts_header">Контактная информация</div>
    <ul id="object_contacts">
        {if $object.pt_adress}
        <li>
            <img src="/img/ico/ico.house.png" alt="адрес" title="Адрес" class="textmarker" />
            <span class="object_contacts_addrs">{$object.pt_adress}</span>
        </li>
        {/if}
        {if $object.pt_phone}
        <li>
            <img src="/img/ico/ico.phone.png" alt="телефон" title="Телефон" class="textmarker" />
            <span class="object_contacts_phone">{$object.pt_phone}</span>
        </li>
        {/if}
        {if $object.pt_worktime}
        <li>
            <img src="/img/ico/ico.clock.png" alt="часы работы" title="Часы работы" class="textmarker" />
            <span class="object_contacts_worktime">{$object.pt_worktime}</span>
        </li>
        {/if}
        {if $object.pt_website}
        <li>
            <img src="/img/ico/ico.web.png" alt="сайт" title="Сайт" class="textmarker" />
            <a href="{$object.pt_website}" rel="nofollow" class="object_contacts_web">{$object.pt_website}</a>
        </li>
        {/if}
    </ul>
</div>
{/if}

{if $object.pt_latitude && $object.pt_longitude}
<input type="hidden" id="mapobj_pt_id" value="{$object.pt_id}" />
<input type="hidden" id="mapobj_pt_latitude" value="{$object.pt_latitude}" />
<input type="hidden" id="mapobj_pt_longitude" value="{$object.pt_longitude}" />
<input type="hidden" id="mapobj_pt_zoom" value="{$object.map_zoom}" />
<input type="hidden" id="mapobj_pt_name" value="{$object.pt_name}" />
<input type="hidden" id="mapobj_pt_type_pic" value="{$object.tp_icon}" />
<input type="hidden" id="pan_sw_lat" value="{$object.sw_ne.sw.lat}" />
<input type="hidden" id="pan_sw_lon" value="{$object.sw_ne.sw.lon}" />
<input type="hidden" id="pan_ne_lat" value="{$object.sw_ne.ne.lat}" />
<input type="hidden" id="pan_ne_lon" value="{$object.sw_ne.ne.lon}" />
{/if}

<div class="PageObjectSharing">
    <script type="text/javascript" src="https://yandex.st/share/share.js" charset="utf-8" defer="defer"></script>
    <div class="yashare-auto-init" data-yashareL10n="ru" data-yashareType="none" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki"></div>
</div>


<div class="CommonYandexAdverts" id="PageObjectAdvertsBlock"></div>
