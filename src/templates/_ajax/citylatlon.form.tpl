<p><b>Карта региона</b> <span id="city_name">{$city.pc_title}</span></p>
<input type="hidden" id="city_id" value="{$city.pc_id}" />
<input type="hidden" id="city_zoom" value="{$city.zoom}" />
<input type="hidden" id="mapcenter_lat" value="{$city.map_center.lat}" />
<input type="hidden" id="mapcenter_lon" value="{$city.map_center.lon}" />
<input type="hidden" id="obj_point_h" value="{$city.map_point}" />
<input type="hidden" id="obj_typeicon_h" value="star.png" />
<hr />
<p>
    широта: <input type="text" id="city_lat" value="{$city.pc_latitude}" />&deg;,
    долгота <input type="text" id="city_lon" value="{$city.pc_longitude}" />&deg;

    <span class="formhandler" id="pc_latlon_handler">
        <input type="button" value="сохранить" class="dosave" />
        <input type="button" value="отменить" class="doesc" />
    </span>

</p>
<div style="margin: 0 auto;width:600px;">
    <div id="objfinder_map"></div>
</div>
<script type="text/javascript">
    $("body").live("afterShowWindByURL", function () {
        showMap($("#mapcenter_lat").val(), $("#mapcenter_lon").val(), $("#city_zoom").val(), $("#obj_point_h").val());
        $('#objfinder_map').focus();
        $("body").die("afterShowWindByURL");
    });
</script>