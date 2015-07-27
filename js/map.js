ymaps.ready(function () {
    var latitude = 55.75949;
    var longitude = 37.63252;
    var zoom = 10;

    var hashCenter = getHashParam('center');
    var hashZoom = getHashParam('zoom');
    if (hashCenter) {
        latitude = hashCenter.split(',')[1];
        longitude = hashCenter.split(',')[0];
        zoom = hashZoom;
    } else {
        ymaps.geolocation.get({
            autoReverseGeocode: true
        }).then(function (result) {
            var c = result.geoObjects.get(0).geometry.getCoordinates();
            map.setCenter(c, zoom, {
                checkZoomRange: true
            });
        });
    }

    var map = new ymaps.Map('maponpage', {
        center: [longitude, latitude],
        zoom: zoom,
        type: "yandex#map",
        controls: ["zoomControl", "typeSelector", "geolocationControl", "routeEditor", "fullscreenControl"]
    }, {
        minZoom: 1
    });
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


    var objects_all = [];
    var objects_cnt = 0;


    var bounds = map.getBounds();
    var boundsparams = [
        'lln=' + bounds[0][0],
        'llt=' + bounds[0][1],
        'rln=' + bounds[1][0],
        'rlt=' + bounds[1][1]
    ];
    ymaps.geoXml.load("https://culttourism.ru/map/common/?" + boundsparams.join('&')).then(function (res) {
        if (res.mapState && !hashCenter) {
            res.mapState.applyToMap(map);
        }
        var arr = [];
        res.geoObjects.each(function (obj) {
            var oid = parseInt(obj.properties.get("metaDataProperty").AnyMetaData.pid);
            objects_all[oid] = obj;
            objects_cnt++;
        });
        objects_all.map(function (object) {
            return arr.push(object);
        });
        $('#mapdata_points').text(objects_cnt);
        cluster.add(arr);
        map.geoObjects.add(cluster);
    });

    map.events.add(['boundschange', 'typechange'], function () {
        var params = [
            'center=' + map.getCenter(),
            'zoom=' + map.getZoom()
        ];
        window.history.replaceState({}, '', location.href.replace(location.hash,"") + '#' + params.join('&'));
        bounds = map.getBounds();
        boundsparams = [
            'lln=' + bounds[0][0],
            'llt=' + bounds[0][1],
            'rln=' + bounds[1][0],
            'rlt=' + bounds[1][1]
        ];
        ymaps.geoXml.load("https://culttourism.ru/map/common/?" + boundsparams.join('&')).then(function (res) {
            var arr = [];
            res.geoObjects.each(function (obj) {
                var oid = parseInt(obj.properties.get("metaDataProperty").AnyMetaData.pid);
                if (!objects_all[oid]) {
                    objects_all[oid] = obj;
                    arr.push(obj);
                    objects_cnt++;
                }
            });
            $('#mapdata_points').text(objects_cnt);
            cluster.add(arr);
            map.geoObjects.add(cluster);
        });
    });

    ymaps.geolocation.get().then(function (result) {
        $('#mapdata_location').text(result.geoObjects.get(0).properties.get('text'));
    });
});


function getHashParam(name, location) {
    location = location || window.location.hash;
    var res = location.match(new RegExp('[#&]' + name + '=([^&]*)', 'i'));
    return (res && res[1] ? res[1] : false);
}