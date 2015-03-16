ymaps.ready(function () {
        var myMap = new ymaps.Map("list-map", {
            center: [37, 55],
            zoom: 10,
            behaviors: ['default', 'scrollZoom']
        });
        myMap.controls.add("zoomControl").add("mapTools").add(new ymaps.control.TypeSelector(["yandex#map", "yandex#hybrid"]));
        $.getScript('/js/nmap-autoswitcher/nmap-autoswitcher.js', function () {
            var autoSwitcher = new AutoSwitcher();
            autoSwitcher.addToMap(myMap);
        });

        ymaps.geoXml.load("https://culttourism.ru/map/list/?lid=" + $("#list-id").val())
                .then(function (res) {
                    var bounds = res.mapState.getBounds();
                    myMap.geoObjects.add(res.geoObjects);
                    myMap.setBounds(bounds);
                },
                        function (error) {
                        });
    });