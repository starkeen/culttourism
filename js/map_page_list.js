ymaps.ready(function () {
    if ($("#list-id").val()) {
        var myMap = new ymaps.Map("list-map", {
            center: [37, 55],
            zoom: 10,
            type: "yandex#map",
            controls: ["zoomControl", "typeSelector", "geolocationControl"]
        });
        ymaps.geoXml.load("https://culttourism.ru/map/list/?lid=" + $("#list-id").val())
                .then(function (res) {
                    var bounds = res.mapState.getBounds();
                    myMap.geoObjects.add(res.geoObjects);
                    myMap.setBounds(bounds);
                });
    }
});