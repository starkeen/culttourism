ymaps.ready(function() {
    var myMap = new ymaps.Map("city_map", {
        center: [$('#mapcity_pc_longitude').val(), $('#mapcity_pc_latitude').val()],
        zoom: $('#mapcity_pc_zoom').val(),
        behaviors: ['default', 'scrollZoom']
    });
    myMap.controls.add("zoomControl").add("mapTools").add(new ymaps.control.TypeSelector(["yandex#map", "yandex#hybrid"]));
    $.getScript('/js/nmap-autoswitcher/nmap-autoswitcher.js', function () {
        var autoSwitcher = new AutoSwitcher();
        autoSwitcher.addToMap(myMap);
    });
    ymaps.geoXml.load('http://culttourism.ru/ajax/YMapsML/getcitypoints/?cid=' + $('#mapcity_pc_id').val()).then(function (res) {
        myMap.geoObjects.add(res.geoObjects);
        if (res.mapState) {
            res.mapState.applyToMap(myMap);
        }
    });
});