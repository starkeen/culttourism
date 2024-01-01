<script type="text/javascript" src="/js/editor.js" defer="defer"></script>

<h2>{$city.pc_title}</h2>
<hr />

<form method="post">
    <div class="city-details-main">
        <table>
            <tr>
                <td nowrap>ключевые слова</td>
                <td colspan="3" style="width:100%"><input type="text" name="keywds" id="city_keywds" value="{$city.pc_keywords}" style="width:99%" /></td>
                <td><span class="city_form_hint" id="city_sign_keywds" title="оптимально до 150"></span></td>
            </tr>
            <tr>
                <td nowrap>краткое описание (до 200 знаков)</td>
                <td colspan="3"><textarea name="descr" id="city_descr" style="width:99%">{$city.pc_description}</textarea></td>
                <td><span class="city_form_hint" id="city_sign_descr" title="обычно 150-200"></span></td>
            </tr>
            <tr>
                <td nowrap>координаты</td>
                <td colspan="4">
                    широта (latitude): <input type="text" name="latitude" id="pc_latitude" value="{$city.pc_latitude}" style="width: 60pt" />,
                    долгота (longitude): <input type="text" name="longitude" id="pc_longitude" value="{$city.pc_longitude}" style="width: 60pt" />
                    <a id="citymap_finder" href="#" style="font-size:smaller;">[на&nbsp;карте]</a>
                </td>
            </tr>
            <tr>
                <td nowrap>OSM id</td>
                <td colspan="4">
                    <input type="text" name="osm_id" value="{$city.pc_osm_id}" style="width:80px;text-align:right;" />
                </td>
            </tr>
            <tr>
                <td nowrap>родительный падеж&hellip; (чего?) </td>
                <td><input type="text" name="inwhere" value="{$city.pc_inwheretext}" style="width: 100%" /></td>
                <td>транслит</td>
                <td><input type="text" name="translit" value="{$city.pc_title_translit}" style="width: 100%" /></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td nowrap>англоязычное название</td>
                <td><input type="text" name="title_eng" value="{$city.pc_title_english}" style="width: 100%" /></td>
                <td>синонимы</td>
                <td><input type="text" name="synonym" value="{$city.pc_title_synonym}" style="width: 100%" /></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td nowrap>красивый анонс</td>
                <td colspan="3"><textarea name="anons" id="city_anons" style="width:99%;">{$city.pc_announcement}</textarea></td>
                <td><span class="city_form_hint" id="city_sign_anons" title="обычно 150-200">0</span></td>
            </tr>
            <tr>
                <td nowrap>URL страницы</td>
                <td colspan="4" style="width:100%">http://{$baseurl}<input type="text" name="url" value="{$city.url}" style="width: 50%" /></td>
            </tr>
        </table>
    </div>


    <div class="city-details-photos">
        <input type="hidden" name="photo_id" value="0" />
        {foreach from=$photos item=ph}
        <div class="city-details-photoitem">
            <input type="radio" name="photo_id" value="{$ph.ph_id}" {if $city.pc_coverphoto_id == $ph.ph_id}checked{/if} />
                   <br />
            <img src="{$ph.ph_src}" alt="" />
            <br />
            {$ph.ph_title}
            <br />
            <a href="{$ph.ph_link}">link</a>
        </div>
        {/foreach}
    </div>


    <input type="hidden" name="web" value="{$city.pc_website}" />
    <input type="submit" value="сохранить" />
</form>


<hr />

<div class="city-details-metas">
    <input type="hidden" id="city_meta_pcid" value="{$city.pc_id}" />
    <table id="city_meta_table">
        {foreach from=$meta item=item}
        <tr id="city_meta_row_{$item.cf_id}">
            <td>{$item.cf_title}</td>
            <td>
                <input type="text" id="city_meta_value_{$item.cf_id}" value="{$item.cd_value}" />
            </td>
            <td>
                <img src="/img/btn/btn.tick.png" class="button_active" data-act="edit" data-cfid="{$item.cf_id}" />
                <img src="/img/btn/btn.delete.png" class="button_active" data-act="del" data-cfid="{$item.cf_id}" />
            </td>
        </tr>
        {/foreach}
        <tr>
            <td>
                <select id="city_meta_add_cf">
                    {foreach from=$ref_meta item=ritem}
                    <option value="{$ritem.cf_id}">{$ritem.cf_title}</option>
                    {/foreach}
                </select>
            </td>
            <td><input type="text" id="city_meta_add_value" value="" /></td>
            <td><img src="/img/btn/btn.add.png" class="button_active" data-act="add" /></td>
        </tr>
    </table>
</div>

<div class="city-details-yandex">
    <img src="/img/yandex.png" alt="Поиск от Яндекс">
    {if $wordstat !== null}
        Вес: {$wordstat->ws_weight_min} < {$wordstat->ws_weight} < {$wordstat->ws_weight_max}<br />
        Позиция: {$wordstat->ws_position}
    {else}
        Данных нет
    {/if}
</div>

<style>
    td {
        vertical-align: top;
        padding:1px;
    }
    .city_form_hint {
        color:#777;
        font-size:80%;
    }
    .button_active {
        cursor:pointer;
    }
</style>
