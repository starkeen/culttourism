<p>
    <b>Координаты объекта</b> <span id="obj_name">{$point.pt_name}</span> [{$point.pc_title}]
    {if $point.pt_adress}<br />Адрес: <span id="obj_addr_searcher">{$point.pt_adress}</span>{/if}
    <input type="button" value="Обновить" class="savereverse" />
</p>
<input type="hidden" id="obj_id" value="{$point.pt_id}" />
<input type="hidden" id="obj_zoom" value="{$point.zoom}" />
<input type="hidden" id="mapcenter_lat" value="{$point.map_center.lat}" />
<input type="hidden" id="mapcenter_lon" value="{$point.map_center.lon}" />
<hr />
<p>
    широта: <input type="text" id="obj_lat" value="{$point.pt_latitude}" />&deg;,
    долгота <input type="text" id="obj_lon" value="{$point.pt_longitude}" />&deg;

    <input type="button" value="GO" class="dogo" />
    <input type="button" value="Адрес" class="doreverse" />
    &nbsp;&nbsp;&nbsp;&nbsp;
    <span class="formhandler" id="pt_latlon_handler">
        <input type="button" value="сохранить" class="dosave" />
        <input type="button" value="отменить" class="doesc" />
    </span>

</p>
<div style="margin: 0 auto;width:600px;">
    <input type="hidden" id="obj_point_h" value="{$point.map_point}" />
    <input type="hidden" id="obj_typeicon_h" value="{$point.tp_icon}" />
    <div id="objfinder_map"></div>
</div>
<script type="text/javascript">
    $("body").live("afterShowWindByURL", function () {
        showMap($("#mapcenter_lat").val(), $("#mapcenter_lon").val(), $("#obj_zoom").val(), $("#obj_point_h").val());
        $('#objfinder_map').focus();
        $("body").die("afterShowWindByURL");
    });
</script>