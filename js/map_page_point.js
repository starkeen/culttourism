ymaps.ready(function() {
    var myMap = new ymaps.Map("city_map", {
        center: [$('#mapobj_pt_longitude').val(), $('#mapobj_pt_latitude').val()],
        zoom: $('#mapobj_pt_zoom').val(),
        behaviors: ['default', 'scrollZoom']
    }),
    // Создаем метку и задаем изображение для ее иконки
    myPlacemark = new ymaps.Placemark([$('#mapobj_pt_longitude').val(), $('#mapobj_pt_latitude').val()], {
        balloonContent: $('#mapobj_pt_name').val()
    }, {
        iconImageHref: '/img/points/xmap/' + $('#mapobj_pt_type_pic').val(), // картинка иконки
        iconImageSize: [55, 55], // размеры картинки
        iconImageOffset: [-27, -55] // смещение картинки
    });
    myMap.controls.add("zoomControl").add("mapTools").add(new ymaps.control.TypeSelector(["yandex#map", "yandex#hybrid"]));
    $.getScript('/js/nmap-autoswitcher/nmap-autoswitcher.js', function () {
        var autoSwitcher = new AutoSwitcher();
        autoSwitcher.addToMap(myMap);
    });
    // Добавление метки на карту
    myMap.geoObjects.add(myPlacemark);
});