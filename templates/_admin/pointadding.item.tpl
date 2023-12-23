<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<input type="hidden" id="pointadding-item-referer" value="{$referer}" />

<table class="detailtable">
    <tr>
        <th>Название</th>
        <td>
            <input type="text" class="pointadding-item-title" value="{$claim.cp_title|escape:'html'}" />
            <input type="hidden" id="pointadding-item-id" value="{$claim.cp_id}" />
            <input type="button" class="pointadding-item-title-quotes" value="&laquo;&raquo;" />
        </td>
        <td>Возможные аналоги</td>
    </tr>
    <tr>
        <th>Тип</th>
        <td>
            {foreach from=$ref_types item=tp}
            <a href="#" class="pointadding-item-select-type {if $tp.tp_id == $claim.cp_type_id}m_active{/if}" title="{$tp.tp_short}" data-value="{$tp.tp_id}">
                <img src="/img/points/x32/{$tp.tp_icon}" />
            </a>
            {/foreach}
        </td>
        <td rowspan="4" class="h_valign_top">
            <ul class="pointadding-item-analogs-list"></ul>
            <div class="pointadding-item-analogs-error"></div>
            <br />
            <div class="pointadding-item-analogs-run m_center"><input type="button" value="искать" /></div>
        </td>
    </tr>
    <tr>
        <th>Регион</th>
        <td>
            <a href="#" class="pointadding-item-city-typed" title="Как ввел пользователь">{$claim.cp_city|escape:'html'} =></a>
            <input type="hidden" id="pointadding-item-city-pcid" value="{$claim.cp_citypage_id}" />
            <input type="text" class="pointadding-item-city-suggest m_hide" value="{$claim.cp_city|escape:'html'}" />
            <a href="#" class="pointadding-item-city-pctitle" target="_blank"></a>
            <input type="button" class="pointadding-item-addr-city" value="↓" />
        </td>
    </tr>
    <tr>
        <th>Координаты</th>
        <td>
            N<input type="text" id="pointadding-item-geo-lat" value="{$claim.cp_latitude}" />
            E<input type="text" id="pointadding-item-geo-lon" value="{$claim.cp_longitude}" />
            <input type="hidden" id="pointadding-item-geo-zoom" value="{$claim.cp_zoom}" />
            <input type="button" class="pointadding-item-geo-set" value="MAP" data-mapstate="0" />
            <input type="button" class="pointadding-item-geo-go m_hide" value="GO" />
            <input type="button" class="pointadding-item-geo-get m_hide" value="FIND" />
            &nbsp;&nbsp;
            <input type="button" class="pointadding-item-geo-reverse" value="↓" />
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="pointadding-item-textcontainer">
                <textarea class="pointadding-item-text" id="pointadding-item-text">{$claim.cp_text|escape:'html'}</textarea>
            </div>
            <div class="pointadding-item-mapcontainer m_hide">
                <div class="pointadding-item-map" id="pointadding-item-map"></div>
            </div>
        </td>
    </tr>
    <tr>
        <th>Адрес</th>
        <td>
            <input type="button" class="pointadding-item-addr-cut" value="г." />
            <input type="text" class="pointadding-item-addr" value="{$claim.cp_addr|escape:'html'}" />
        </td>
        <td class="pointadding-item-analogs-ignore m_center">
            <input type="button" class="m_color_red" value="уже есть" data-state="5" />
        </td>
    </tr>
    <tr>
        <th>Телефон</th>
        <td>
            <input type="text" class="pointadding-item-phone m_width_full" value="{$claim.cp_phone}" />
        </td>
        <td rowspan="2" class="h_valign_top" nowrap="nowrap">
            <b>Отправитель:</b><br/>
            {$claim.cp_sender}
        </td>
    </tr>
    <tr>
        <th>График</th>
        <td>
            <input type="text" class="pointadding-item-worktime m_width_full" value="{$claim.cp_worktime|escape:'html'}" />
        </td>
    </tr>
    <tr>
        <th>Веб-сайт</th>
        <td>
            <input type="text" class="pointadding-item-web m_width_full" value="{$claim.cp_web}" />
        </td>
        <td rowspan="2" class="h_valign_top">
            <b>Источник:</b><br/>
            <a href="{$claim.cp_referer}" target="_blank">{$claim.cp_referer}</a>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="m_center">
            <input type="button" class="pointadding-item-save m_color_green" value="Сохранить" data-state="25" data-return="1" />
            &nbsp;&nbsp;&nbsp;
            <a href="?">вернуться</a>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="pointadding-item-analogs-ignore">
            <input type="button" class="pointadding-item-skip m_color_red" value="Не подходит" data-state="8" />
            &nbsp;&nbsp;&nbsp;
            <input type="button" class="pointadding-item-skip m_color_red" value="Спам" data-state="7" />
        </td>
        <td class="m_center">
            <input type="button" class="pointadding-item-confirm m_color_green" value="Внести" />
        </td>
    </tr>
</table>

<script type="text/javascript"
        src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&coordorder=longlat&apikey={$yandex_maps_key}"
        defer="defer"></script>
<script type="text/javascript" src="/js/admin/addpoint.js" defer="defer"></script>

<style type="text/css">
    .pointadding-item-title {
        width: 300px;
    }
    .pointadding-item-select-type {
        display:inline-block;
        width:30px;
        height:25px;
        padding:2px;
        text-align: center;
        vertical-align: middle;
        text-decoration: none;
        border: 1px solid #ddd;
    }
    .pointadding-item-select-type.m_active {
        border: 1px solid red;
    }
    .pointadding-item-select-type img {
        width: 24px;
    }
    .pointadding-item-text {
        height: 250px;
        width: 300px;
    }
    #pointadding-item-geo-lat, #pointadding-item-geo-lon {
        width:80px;
    }
    .pointadding-item-textcontainer {
        width: 656px;
    }
    .pointadding-item-mapcontainer {
        width: 656px;
    }
    .pointadding-item-map {
        height: 300px;
        width: 654px;
        background-color: #efefef;
        border:1px solid #ddd;
    }
    .pointadding-item-addr {
        width: 600px;
    }
    .pointadding-item-city-typed {
        font-style: italic;
        color:blue;
        text-decoration: none;
        border-bottom: 1px dotted blue;
    }
    .pointadding-item-analogs-error {
        color:red;
        font-size: 80%;
        max-width: 200px;
    }
    .m_hide {
        display:none;
    }
    .m_color_red {
        color: #fff;
        background-color: red;
    }
    .m_color_green {
        color: #fff;
        background-color: green;
    }
</style>
