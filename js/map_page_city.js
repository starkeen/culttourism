ymaps.ready(function() {
    var myMap = new ymaps.Map("city_map", {
        center: [$('#mapcity_pc_longitude').val(), $('#mapcity_pc_latitude').val()],
        zoom: $('#mapcity_pc_zoom').val(),
        type: "yandex#map",
        controls: ["zoomControl", "typeSelector", "geolocationControl"]
    });
    /*
    myMap.controls.add("zoomControl").add("mapTools").add(new ymaps.control.TypeSelector(["yandex#map", "yandex#hybrid"]));
    $.getScript('/js/nmap-autoswitcher/nmap-autoswitcher.js', function() {
        var autoSwitcher = new AutoSwitcher();
        autoSwitcher.addToMap(myMap);
    });
    */
    ymaps.geoXml.load('https://culttourism.ru/map/city/?cid=' + $('#mapcity_pc_id').val()).then(function(res) {
        myMap.geoObjects.add(res.geoObjects);
        if (res.mapState) {
            res.mapState.applyToMap(myMap);
        }
    });
});