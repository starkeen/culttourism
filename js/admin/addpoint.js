$(document).ready(function () {
    var item_id = $("#pointadding-item-id").val();
    var item_latitude = $("#pointadding-item-geo-lat").val();
    var item_longitude = $("#pointadding-item-geo-lon").val();
    var referer = $("#pointadding-item-referer").val();

    if (item_latitude != null && item_longitude != null) {
        $(".pointadding-item-geo-change").removeClass("m_hide");
    } else {
        $(".pointadding-item-geo-set").removeClass("m_hide");
    }

    $(".pointadding-item-title-quotes").click(function () {
        // добавляем кавычки к последнему слову в наборе
        var words = $(".pointadding-item-title").val().split(" ");
        var last = '«' + words.pop().trim() + '»';
        $(".pointadding-item-title").val((words.join(" ").trim() + ' ' + last).trim());
    });

    $(".pointadding-item-addr-cut").click(function () {
        //обрезаем адрес от начала до города
        var addr = $(".pointadding-item-addr").val();
        var city = $(".pointadding-item-city-pctitle").text();
        var pos = addr.indexOf(city);
        if (pos !== -1) {
            $(".pointadding-item-addr").val('г. ' + addr.substr(pos, addr.length));
        }
        var cleaned = $(".pointadding-item-addr").val().replace(/\.(\d+)/g, ". $1").replace(/\s{2,}/g, ' ');
        $(".pointadding-item-addr").val(cleaned);
    });

    $('#pointadding-item-city-pcid').change(function () {
        $.getJSON("addpoints.php", {
            act: "get_citypage",
            id: item_id,
            pc_id: $("#pointadding-item-city-pcid").val()
        }, function (data) {
            if (data.state) {
                $(".pointadding-item-city-pctitle").text(data.citypage.pc_title);
                $(".pointadding-item-city-pctitle").attr("href", data.citypage.url);
            } else {
                console.log("error", data);
            }
        });
    });
    if ($("#pointadding-item-city-pcid").val() !== 0) {
        $("#pointadding-item-city-pcid").change();
    }
    $(".pointadding-item-analogs-run input").click(function () {
        // поиск аналогов, добавленных ранее
        var pname = $(".pointadding-item-title").val();
        $(".pointadding-item-analogs-error").empty();
        $(".pointadding-item-analogs-list").empty();
        $.getJSON("addpoints.php", {
            act: "get_analogs",
            id: item_id,
            pname: pname
        }, function (data) {
            if (data.state) {
                $.each(data.founded, function (i, item) {
                    $(".pointadding-item-analogs-list").append("<li>" + item.title.replace(" | Культурный туризм", "") + "</li>");
                });
                $(".pointadding-item-analogs-ignore").removeClass("m_hide");
            } else {
                $(".pointadding-item-analogs-error").text(data.error);
            }
        });
        return false;

    });
    $(".pointadding-item-analogs-ignore input").click(function () {
        // при наличии аналогов точку отправляем в игнор
        $.getJSON("addpoints.php", {
            act: "set_ignore",
            id: item_id,
            state_id: $(this).data("state")
        }, function (data) {
            if (data.state) {
                document.location.href = referer;
            } else {
                $(".pointadding-item-analogs-error").text(data.error);
            }
        });
    });
    $(".pointadding-item-city-typed").click(function () {
        // подбор страницы для размещения точки
        $(".pointadding-item-city-typed").hide();
        $(".pointadding-item-city-suggest").show().focus().change();
        $(".pointadding-item-city-suggest").blur(function () {
            $(".pointadding-item-city-suggest").hide();
            $(".pointadding-item-city-typed").show();
        });
        return false;
    });
    $(".pointadding-item-geo-set").click(function () {
        // указание координат по карте
        if ($(this).data("mapstate") == 0) {
            $(this).data("mapstate", 1);
            $(".pointadding-item-textcontainer").addClass("m_hide");
            $(".pointadding-item-mapcontainer").removeClass("m_hide");
            $(".pointadding-item-geo-go").removeClass("m_hide");
            $(".pointadding-item-geo-get").removeClass("m_hide");
            if (item_latitude == 0 && item_longitude == 0) {
                $(".pointadding-item-geo-get").click();
            }
        } else {
            $(this).data("mapstate", 0);
            $(".pointadding-item-textcontainer").removeClass("m_hide");
            $(".pointadding-item-mapcontainer").addClass("m_hide");
            $(".pointadding-item-geo-go").addClass("m_hide");
            $(".pointadding-item-geo-get").addClass("m_hide");
        }
    });

    $(".pointadding-item-select-type").click(function () {
        //выбор типа точки
        var ptype = $(this).data("value");
        $.getJSON("addpoints.php", {
            act: "set_type",
            id: item_id,
            ptype: ptype
        }, function (data) {
            if (data.state) {
                $(".pointadding-item-select-type").each(function (item) {
                    $(this).removeClass("m_active");
                    if ($(this).data("value") == data.data.cp_type_id) {
                        $(this).addClass("m_active");
                    }
                });
            } else {
                console.log("error", data);
            }
        });
        return false;
    });
    $(".pointadding-item-save").click(function () {
        //сохранение точки в базу
        var ret = $(this).data("return");
        $.post("addpoints.php?act=save_candidate&id=" + $("#pointadding-item-id").val(), {
            title: $(".pointadding-item-title").val(),
            text: $(".pointadding-item-text").val(),
            addr: $(".pointadding-item-addr").val(),
            phone: $(".pointadding-item-phone").val(),
            worktime: $(".pointadding-item-worktime").val(),
            web: $(".pointadding-item-web").val(),
            lat: $("#pointadding-item-geo-lat").val(),
            lon: $("#pointadding-item-geo-lon").val(),
            zoom: $("#pointadding-item-geo-zoom").val(),
            state_id: $(this).data("state")
        }, function (answer) {
            if (answer.state) {
                if (ret) {
                    document.location.href = referer;
                } else {
                    $("body").trigger('afterSavingCandidate');
                }
            }
        });
    });
    $(".pointadding-item-confirm").click(function () {
        //отправка точки на страницу
        $(".pointadding-item-save").data("return", 0).click();
        $(".pointadding-item-save").data("return", 1);
    });
    $("body").live("afterSavingCandidate", function () {
        $.getJSON("addpoints.php", {
            act: "move",
            id: item_id
        }, function (data) {
            if (data.state) {
                document.location.href = referer;
            } else {
                alert("Error: " + data.error.join(";\n"));
            }
        });
    });


    $(".pointadding-item-city-suggest").autocomplete({
        serviceUrl: "addpoints.php?act=citysuggest&id=" + item_id,
        minChars: 2,
        paramName: "query",
        "width": 400,
        onSelect: function (suggestion) {
            $('#pointadding-item-city-pcid').val(suggestion.pcid);
            $.getJSON("addpoints.php", {
                act: "set_citypage",
                id: item_id,
                pc_id: suggestion.pcid
            }, function (data) {
                if (data.state) {
                    $(".pointadding-item-city-pctitle").text(suggestion.value);
                    $(".pointadding-item-city-pctitle").attr("href", suggestion.url);
                } else {
                    console.log("error", data);
                }
            });
        }
    });
    $("#pointadding-item-text").ckeditor({
        customConfig: "/config/config.cke4.js",
        height: '230px',
        toolbar: "Lite"
    });






    ymaps.ready(function () {
        var mapcenter = [$("#pointadding-item-geo-lon").val(), $("#pointadding-item-geo-lat").val()];
        var map = new ymaps.Map('pointadding-item-map', {
            center: mapcenter,
            zoom: $("#pointadding-item-geo-zoom").val(),
            type: "yandex#publicMap",
            controls: ["zoomControl", "typeSelector", "geolocationControl", "routeEditor", "fullscreenControl"]
        }, {
            minZoom: 1
        });
        var myPlacemark = new ymaps.Placemark(mapcenter, {
            hintContent: "Перетащите для изменения координат",
            balloonContent: "Новая точка"
        }, {
            iconLayout: 'default#image',
            iconImageHref: '/img/points/xmap/star.png', // картинка иконки
            iconImageSize: [55, 55], // размеры картинки
            iconImageOffset: [-27, -55], // смещение картинки
            draggable: true // Метку можно перетаскивать, зажав левую кнопку мыши.
        });
        myPlacemark.events.add("dragend", function () {
            var coords = myPlacemark.geometry.getCoordinates();
            $("#pointadding-item-geo-lat").val(coords[1]);
            $("#pointadding-item-geo-lon").val(coords[0]);
            $("#pointadding-item-geo-zoom").val(map.getZoom());
        });
        map.geoObjects.add(myPlacemark);
        map.events.add('click', function (e) {
            var coords = e.get('coords');
            myPlacemark.geometry.setCoordinates(coords);
            $("#pointadding-item-geo-lat").val(coords[1]);
            $("#pointadding-item-geo-lon").val(coords[0]);
            $("#pointadding-item-geo-zoom").val(map.getZoom());
        });

        $(".pointadding-item-geo-get").click(function () {
            ymaps.geocode($(".pointadding-item-addr").val(), {
                kind: 'house',
                boundedBy: map.getBounds(),
                results: 20
            }).then(function (res) {
                map.geoObjects.add(res.geoObjects);
                res.geoObjects.each(function (obj) {
                    map.panTo(obj.geometry.getCoordinates(), {
                        flying: true,
                        delay: 0,
                        duration: 300
                    });
                });
            });
        });

        $(".pointadding-item-geo-go").click(function () {
            var point = [parseFloat($("#pointadding-item-geo-lon").val()), parseFloat($("#pointadding-item-geo-lat").val())];
            map.panTo(point, {
                flying: true,
                delay: 0,
                duration: 500
            });
            return false;
        });
    });
});
