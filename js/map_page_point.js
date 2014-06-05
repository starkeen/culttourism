ymaps.ready(function() {
    var myMap = new ymaps.Map("city_map", {
        center: [$('#mapobj_pt_longitude').val(), $('#mapobj_pt_latitude').val()],
        zoom: $('#mapobj_pt_zoom').val(),
        behaviors: ['default', 'scrollZoom']
    });
    myMap.controls.add("zoomControl").add("mapTools").add(new ymaps.control.TypeSelector(["yandex#map", "yandex#hybrid"]));
    $.getScript('/js/nmap-autoswitcher/nmap-autoswitcher.js', function() {
        var autoSwitcher = new AutoSwitcher();
        autoSwitcher.addToMap(myMap);
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
    var bounds = myMap.getBounds();
    var boundsparams = [
        'lln=' + bounds[0][0],
        'llt=' + bounds[0][1],
        'rln=' + bounds[1][0],
        'rlt=' + bounds[1][1]
    ];
    var objects_all = [];
    ymaps.geoXml.load("http://culttourism.ru/map/common/?oid=" + $("#mapobj_pt_id").val() + "&" + boundsparams.join('&')).then(function(res) {
        if (res.mapState) {
            res.mapState.applyToMap(myMap);
        }
        res.geoObjects.each(function(obj) {
            var oid = parseInt(obj.properties._K.metaDataProperty.AnyMetaData.pid);
            objects_all[oid] = obj;
        });
        var arr = [];
        objects_all.map(function(object) {
            return arr.push(object);
        });
        cluster.add(arr);
        myMap.geoObjects.add(cluster);
    });
    myMap.events.add(['boundschange', 'typechange'], function() {
        bounds = myMap.getBounds();
        boundsparams = [
            'lln=' + bounds[0][0],
            'llt=' + bounds[0][1],
            'rln=' + bounds[1][0],
            'rlt=' + bounds[1][1]
        ];
        ymaps.geoXml.load("http://culttourism.ru/map/common/?oid=" + $("#mapobj_pt_id").val() + "&" + boundsparams.join('&')).then(function(res) {
            var arr = [];
            res.geoObjects.each(function(obj) {
                var oid = parseInt(obj.properties._K.metaDataProperty.AnyMetaData.pid);
                if (!objects_all[oid]) {
                    objects_all[oid] = obj;
                    arr.push(obj);
                }
            });
            cluster.add(arr);
            myMap.geoObjects.add(cluster);
        });
    });
});