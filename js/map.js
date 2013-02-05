ymaps.ready(function() {
    hashCenter = getHashParam('center');
    hashZoom = getHashParam('zoom');
    var latitude = null;
    var longitude = null;
    var zoom = null;
    if (hashCenter) {
        latitude = hashCenter.split(',')[1];
        longitude = hashCenter.split(',')[0];
        zoom = hashZoom;
    } else {
        latitude = ymaps.geolocation.latitude;
        longitude = ymaps.geolocation.longitude;
        zoom = ymaps.geolocation.zoom;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;
                if (!latitude || !longitude) {
                    latitude = ymaps.geolocation.latitude;
                    longitude = ymaps.geolocation.longitude;
                    zoom = 12;
                }
            });
        }
        var params = [
        'center=' + longitude + ',' + latitude,
        'zoom=' + zoom
        ];
        window.location.hash = params.join('&');
    }
    var map = new ymaps.Map('maponpage', {
        center: [longitude, latitude],
        zoom: zoom,
        behaviors: ['default', 'scrollZoom'],
        type: 'yandex#publicMap'
    }, {
        minZoom:4
    });
    map.controls.add("zoomControl").add("mapTools").add(new ymaps.control.TypeSelector(["yandex#map", "yandex#satellite", "yandex#hybrid", "yandex#publicMap"]));
                
    var cluster = new ymaps.Clusterer({
        margin: [20],
        clusterIcons: [{
            href: '/img/points/map/cluster.png',
            size: [40, 40],
            offset: [-20, -20]
        }],
        clusterNumbers: [100],
        gridSize: 50
    });
    
    var bounds = map.getBounds();
    var boundsparams = [
    'lln='+bounds[0][0],
    'llt='+bounds[0][1],
    'rln='+bounds[1][0],
    'rlt='+bounds[1][1],
    ];
                
    ymaps.geoXml.load("http://culttourism.ru/ajax/YMapsML/getcommonmap/?"+boundsparams.join('&')).then(function (res) {
        if (res.mapState && !hashCenter)
            res.mapState.applyToMap(map);
        var arr = [];
        res.geoObjects.each(function (obj) {
            arr.push(obj);
        });
        $('#mapdata_points').text(arr.length);
        cluster.add(arr);
        map.geoObjects.add(cluster);
    });
    
    map.events.add(['boundschange', 'typechange'], function () {
        var params = [
        'center=' + map.getCenter(),
        'zoom=' + map.getZoom()
        ];
        window.location.hash = params.join('&');
        bounds = map.getBounds();
        boundsparams = [
        'lln='+bounds[0][0],
        'llt='+bounds[0][1],
        'rln='+bounds[1][0],
        'rlt='+bounds[1][1],
        ];
        ymaps.geoXml.load("http://culttourism.ru/ajax/YMapsML/getcommonmap/?"+boundsparams.join('&')).then(function (res) {
            var arr = [];
            res.geoObjects.each(function (obj) {
                arr.push(obj);
            });
            $('#mapdata_points').text(arr.length);
            cluster.removeAll();
            cluster.add(arr);
            map.geoObjects.remove(cluster);
            map.geoObjects.add(cluster);
        });
    });
    
    var locationparams = [ymaps.geolocation.country, ymaps.geolocation.region, ymaps.geolocation.city];
    $('#mapdata_location').text(locationparams.join(', '));
});
function getHashParam (name, location) {
    location = location || window.location.hash;
    var res = location.match(new RegExp('[#&]' + name + '=([^&]*)', 'i'));
    return (res && res[1] ? res[1] : false);
}