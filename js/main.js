jQuery.error = function (message) {
    ga('send', 'event', 'jQuery Error', message, navigator.userAgent);
};

$(document).ready(function () {
    //------------------------------------- BEST OBJECTS -------------------------------
    $(".obj_best").append("<img class=\"obj_best_pic\" src=\"/img/points/best-24.png\" />");
    //---------------------------------- OPEN OBJECT WINDOW ----------------------------
    var isMobile = window.matchMedia("only screen and (max-width: 750px)");
    if (isMobile.matches) {
        $(".objlink").each(function () {
            $(this).addClass("m_mobile");
        });
    }

    $(".objlink").live("click", function () {
        if ($(this).hasClass("m_mobile")) {
            return true;
        }
        var id = this.href.split("/").pop();
        if (/object[0-9]+.html/gi.test(id)) {
            showWindByURL("/ajax/point/", {
                id: id.slice(6, -5)
            });
        } else {
            showWindByURL("/ajax/point/s/", {
                id: id
            });
        }
        return false;
    });
    $(".city_weather").each(function () {
        var that = this;
        $.get("/city/weather/", {
            lat: $(that).data("lat"),
            lon: $(that).data("lon")
        }, function (dobj) {
            if (dobj.state) {
                $(that).html(dobj.content).removeAttr("title");
            } else {
                $(that).html('загрузить погоду не удалось');
            }
        });
    });
    //=============================---------------------- CITY EDIT ------------------------
    $(".hiddenedit").live("click", function () {
        $(this).addClass("hiddenedit_active");
        this.id == "pc_title_edit" && $("div#pc_title_handler input").show();
        this.id == "pc_text_edit" && $("div#pc_text_handler input").show();
        if (this.id == "pc_text_hidd") {
            $(document).css("cursor", "progress");
            $("#pc_text_edit").ckeditor(function () {
            }, {
                customConfig: "/config/config.cke4.js",
                toolbar: "City"
            });
            $("#pc_text_edit").css("height", "500px").val($(this).html());
            $(this).hide();
            $("#pc_text_handler input").show();
            $("#map_container").hide();
            $("#city_float").hide();
            $("#city_announcement").hide();
            $(document).css("cursor", "default");
        }
        if (this.id == "pt_name_hidd") {//-------- название точки ------
            $(this).hide();
            $("#pt_name_edit").show().focus().addClass("hiddenedit_active").val($(this).html());
            $("div#pt_name_handler input").show();
        }
        if (this.id == "pt_description_hidd") {//---------- описание точки -----
            $(document).css("cursor", "progress");
            $("#pt_description_edit").css("height", "300px").css("width", "100%")
                    .val($(this).html())
                    .ckeditor(function () {
                    }, {
                        customConfig: "/config/config.cke4.js",
                        height: '230px',
                        toolbar: "Lite"
                    });
            $(this).hide();
            $("#pt_description_edit").show().focus().css("height", "0");
            $("div#pt_description_handler input").show();
            $(document).css("cursor", "default");
        }
    });
    //------------------------ SAVE EDIT FIELD ------------
    $(".formhandler input.dosave").live("click", function () {
        if ($(this).parent().attr("id") == "pc_title_handler") {//--- название города
            $(document).css("cursor", "progress");
            $("#pc_title_handler input").hide();
            $.post("/ajax/city/savetitle/?id=" + $("#pc_id").val(), {
                id: $("#pc_id").val(),
                ntitle: $("#pc_title_edit").val()
            },
            function (data) {
                $("div#pc_title_handler input").hide();
                $("#pc_title_edit").removeClass("hiddenedit_active").val(data);
            });
            $(document).css("cursor", "default");
        }

        if ($(this).parent().attr("id") == "pc_text_handler") {//--- описание города
            $(document).css("cursor", "progress");
            $("#pc_text_handler input").hide();
            $.post("/ajax/city/savedescr/?id=" + $("#pc_id").val(), {
                id: $("#pc_id").val(),
                ntext: $("#pc_text_edit").val()
            }, function (a) {
                $("#pc_text_edit").ckeditor(function () {
                    this.destroy();
                });
                $("#pc_text_edit").css("height", "0").removeClass("hiddenedit_active").hide();
                $("#pc_text_hidd").html(a).show().removeClass("hiddenedit_active");
                $("#map_container").show();
                $("#city_float").show();
                $("#city_announcement").show();
            });
            $(document).css("cursor", "default");
        }
        if ($(this).parent().attr("id") == "pt_name_handler") {//--- название точки
            $(document).css("cursor", "progress");
            $("#pt_name_handler input").hide();
            $.post("/ajax/point/savetitle/?id=" + $("#pt_id").val(), {
                id: $("#pt_id").val(),
                nname: $("#pt_name_edit").val()
            }, function (a) {
                $("div#pt_name_handler input").hide();
                $("#pt_name_edit").removeClass("hiddenedit_active").hide();
                $("#pt_name_hidd").text(a).show().removeClass("hiddenedit_active");
                $("#object_id_" + $("#pt_id").val()).text(a);
            });
            $(document).css("cursor", "default");
        }
        if ($(this).parent().attr("id") == "pt_description_handler") {//--- описание точки
            $("div#pt_description_handler input").hide();
            $(document).css("cursor", "progress");
            $.post("/ajax/point/savedescr/?id=" + $("#pt_id").val(), {
                id: $("#pt_id").val(),
                ndesc: $("#pt_description_edit").val()
            }, function (a) {
                $("#pt_description_edit").ckeditor(function () {
                    this.destroy();
                });
                $("#pt_description_edit").css("height", "0").removeClass("hiddenedit_active").hide();
                $("#pt_description_hidd").html(a).show().removeClass("hiddenedit_active")
            });
            $(document).css("cursor", "default");
        }
        if ($(this).parent().attr("id") == "pt_add_handler") {//--- добавление точки
            $(document).css("cursor", "progress");
            $("#pt_add_handler input").hide();
            $("div#pt_add_handler").html("").text("сохраняется...");
            $.post("/ajax/point/savenew/?cid=" + $("#pc_id").val(), {
                cid: $("#pc_id").attr("readonly", true).val(),
                nname: $("#pt_name_add").attr("readonly", true).val(),
                ndesc: $("#pt_description_add").attr("readonly", true).val(),
                nweb: $("#pt_web_add").attr("readonly", true).val(),
                nmail: $("#pt_email_add").attr("readonly", true).val(),
                nphone: $("#pt_phone_add").attr("readonly", true).val(),
                nwork: $("#pt_worktime_add").attr("readonly", true).val(),
                naddr: $("#pt_addr_add").attr("readonly", true).val(),
                nlat: $("#pt_lat").attr("readonly", true).val(),
                nlon: $("#pt_lon").attr("readonly", true).val(),
                nbest: $("#pt_is_best_add").attr("readonly", true).attr("checked")
            }, function (data) {
                $("#pt_description_add").ckeditor(function () {
                    this.destroy()
                });
                var latlontext = "";
                if ($("#pt_lat").val() > 0 && $("#pt_lon").val() > 0)
                    latlontext = "N" + $("#pt_lat").val() + " E" + $("#pt_lon").val();
                else
                    latlontext = "указать";
                $('#whatseelist').append('<tr><td><img class="point_typer" id="type_' + data + '" src="/img/points/x32/star.png" alt="другое" /></td><td><a href="object' + data + '.html" id="object_id_' + data + '" class="objlink" title="подробно: ' + $('#pt_name_add').val() + '">' + $('#pt_name_add').val() + '</a></td><td><a href="#" id="gps_' + data + '" class="point_latlon">' + latlontext + '</a></td><td><img class="point_deleter" id="del_' + data + '" src="/img/btn/ico.delete.gif" /></td></tr>');
                $.modal.close();
            });
            $(document).css("cursor", "default");
        }
        if ($(this).parent().attr("id") == "pt_contacts_handler") {//--- сохранение контактов
            $(document).css("cursor", "progress");
            $("#pt_contacts_handler input").hide();
            $("#pt_cont_adress").text($("#pt_cont_adress_edit").val());
            $("#pt_cont_worktime").text($("#pt_cont_worktime_edit").val());
            $("#pt_cont_phone").text($("#pt_cont_phone_edit").val());
            $("#pt_cont_website").text($("#pt_cont_website_edit").val()).attr("href", $("#pt_cont_website_edit").val());
            $("#pt_cont_email").text($("#pt_cont_email_edit").val()).attr("href", "mailto:" + $("#pt_cont_email_edit").val());
            $.post("/ajax/point/savecontacts/?cid=" + $("#pt_id").val(), {
                cid: $("#pt_id").val(),
                nwebsite: $("#pt_cont_website").attr("readonly", true).text(),
                nemail: $("#pt_cont_email").attr("readonly", true).text(),
                nphone: $("#pt_cont_phone").attr("readonly", true).text(),
                nworktime: $("#pt_cont_worktime").attr("readonly", true).text(),
                nadress: $("#pt_cont_adress").attr("readonly", true).text()
            }, function (a) {
                if (a) {
                    $(".edit_cont").show();
                    $("div#pt_contacts_handler input").hide();
                    $(".hiddenedit_cont").hide();
                    $("#do_cont_edit").show();
                }
            });
            $(document).css("cursor", "default")
        }
        if ($(this).parent().attr("id") == "br_save_handler") {//------ запись в блоге
            var record_id = $("#br_id").val();
            $.post("/blog/saveform/?bid=" + record_id, {
                brid: record_id,
                ntitle: $("#eblog_title").val(),
                ntext: $("#eblog_text").val(),
                ndate: $("#eblog_date").val(),
                ntime: $("#eblog_time").val(),
                nact: ($("#eblog_active").attr("checked") == "checked"),
                nurl: $("#eblog_url").val()
            }, function (data) {
                if (data) {
                    $("#eblog_text").ckeditor(function () {
                        this.destroy();
                    });
                    $.modal.close();
                    document.location = '/blog/?ts=' + Date.now() + '#blog_edit_' + record_id;
                }
            });
        }
    });
    //------------------------ ESCAPE EDIT FIELD ------------
    $(".formhandler input.doesc").live("click", function () {
        if ($(this).parent().attr("id") == "pc_title_handler") {
            $("#pc_title_edit").val($("#pc_title_hidd").val()).removeClass("hiddenedit_active");
            $("div#pc_title_handler input").hide();
        }
        if ($(this).parent().attr("id") == "pc_text_handler") {//------ описание города
            $("#pc_text_edit").ckeditor(function () {
                this.destroy();
            });
            $("#pc_text_edit").css("height", "0").hide();
            $("#pc_text_hidd").show().removeClass("hiddenedit_active");
            $("div#pc_text_handler input").hide();
            $("#map_container").show();
            $("#city_float").show();
            $("#city_announcement").show();
        }
        if ($(this).parent().attr("id") == "pt_name_handler") {//------ имя точки
            $("#pt_name_edit").hide().removeClass("hiddenedit_active");
            $("#pt_name_hidd").show().removeClass("hiddenedit_active");
            $("div#pt_name_handler input").hide();
        }
        if ($(this).parent().attr("id") == "pt_description_handler") {//------ описание точки
            $("#pt_description_edit").ckeditor(function () {
                this.destroy();
            });
            $("#pt_description_edit").hide().removeClass("hiddenedit_active");
            $("#pt_description_hidd").show().removeClass("hiddenedit_active");
            $("#pt_description_handler input").hide();
        }
        if ($(this).parent().attr("id") == "pt_add_handler") {//------ добавление точки
            $("#pt_description_add").ckeditor(function () {
                this.destroy();
            });
            $("body").live("afterShowWindByURL", function () {
                $("#pt_description_add").ckeditor(function () {
                }, {
                    customConfig: "/config/config.cke4.js",
                    height: '250px',
                    toolbar: "Lite"
                });
                $("body").die("afterShowWindByURL");
            });
            $.modal.close();
        }
        if ($(this).parent().attr("id") == "pt_contacts_handler") {//------ сохранение контактов
            $(".edit_cont").show();
            $("div#pt_contacts_handler input").hide();
            $(".hiddenedit_cont").hide();
            $("#do_cont_edit").show();
        }
        if ($(this).parent().attr("id") == "br_save_handler") {//------ запись в блоге
            $("#eblog_text").ckeditor(function () {
                this.destroy();
            });
            $.modal.close();
        }
    });
    $("#pt_is_best_edit").live("change", function () {
        $.post("/ajax/point/savebest/?id=" + $("#pt_id").val(), {
            id: $("#pt_id").val(),
            nstate: $(this).attr("checked")
        });
    });
    //-------------------------------- / CITY EDIT -----------------------------------
    //--------------------------------- POINT CONTACTS -------------------------------
    $("#do_cont_edit").live("click", function () {
        $("#pt_cont_adress_edit").val($("#pt_cont_adress").text());
        $("#pt_cont_worktime_edit").val($("#pt_cont_worktime").text());
        $("#pt_cont_phone_edit").val($("#pt_cont_phone").text());
        $("#pt_cont_website_edit").val($("#pt_cont_website").text());
        $("#pt_cont_email_edit").val($("#pt_cont_email").text());
        $("div#pt_contacts_handler input").show();
        $(this).hide();
        $(".edit_cont").hide();
        $(".hiddenedit_cont").show();
    });
    //----------------------------- / POINT CONTACTS ---------------------------------
    //--------------------------------- POINT ADD ------------------------------------
    $("#do_add_point").click(function () {
        $(document).css("cursor", "progress");
        showWindByURL("/ajax/point/getnewform/", {
            cid: $("#pc_id").val()
        });
        $("body").live("afterShowWindByURL", function () {
            $("#pt_description_add").ckeditor(function () {
            }, {
                customConfig: "/config/config.cke4.js",
                height: '250px',
                toolbar: "Lite"
            });
            $("body").die("afterShowWindByURL");
        });
        $(document).css("cursor", "default");
        return false;
    });
    //---------------------------------------- /  POINT ADD --------------------------
    //------------------------------------------- POINT DEL --------------------------
    $(".point_deleter").live("click", function () {
        var a = this.id.split("_"), g = $(this).parents("tr");
        confirm('Действительно удалить точку?') && $.post("/ajax/point/delpoint/?pid=" + a[1], {
            pid: a[1]
        }, function (i) {
            i && $(g).remove();
        })
    });
    //---------------------------------------- /  POINT DEL --------------------------
    //------------------------------------------ POINT TYPE --------------------------
    $(".point_typer").live("click", function () {
        showWindByURL("/ajax/pointtype/getform/", {
            pid: this.id.split("_")[1]
        });
    });
    $("#type_selector tr").live("click", function () {
        var a = this.id.split("_"), g = $("#pt_id").val();
        $.post("/ajax/pointtype/savetype/?pid=" + $("#pt_id").val(), {
            pid: g,
            ntype: a[1]
        }, function (i) {
            $("img#type_" + g + ".point_typer").attr("src", "/img/points/x32/" + i);
            $.modal.close();
        });
    });
    $("a.check_all").click(function () {
        $("input.export_check").attr("checked", "true");
        return false;
    });
    $("a.check_not").click(function () {
        $("input.export_check").removeAttr("checked");
        return false;
    });
    $("a.export_about").click(function () {
        showWindByURL("/ajax/page/gps/", {});
        return false;
    });
    //--------------------------------------- /  POINT TYPE --------------------------
    //------------------------------------------ FEEDBACK --------------------------
    $("#captchahelp").click(function () {
        stamp = new Date;
        $("#norobotpic").attr("src", "/feedback/getcapt/" + stamp.getTime());
        return false;
    });
    //--------------------------------------- POINT GPS ------------------------------
    $(".point_latlon").live("click", function () {
        showWindByURL("/ajax/point/getformGPS/", {
            pid: this.id.split("_")[1]
        });
        return false;
    });
    $("#pt_latlon_handler input.dosave").live("click", function () {//----------- save
        $.post("/ajax/point/saveformGPS/?pid=" + $("#obj_id").val(), {
            pt_lat: $("#obj_lat").val(),
            pt_lon: $("#obj_lon").val(),
            pt_zoom: $("#obj_zoom").val()
        },
        function (resp) {
            if (resp) {
                $("#gps_" + $("#obj_id").val()).text(resp);
                $.modal.close()
            }
        })
    });
    $("#pt_latlon_handler input.doesc").live("click", function () {//------------ escape
        $.modal.close();
    });
    //------------------------------------- / POINT GPS ------------------------------
    //------------------------------------ CITY GPS ----------------------------------
    $("#citymap_finder").click(function () {
        showWindByURL("/ajax/city/getformGPS/", {
            cid: document.location.search.split("city_id=")[1]
        });
        return false;
    });
    $("#pc_latlon_handler input.dosave").live("click", function () {//----------- save
        $.post("/ajax/city/saveformGPS/?cid=" + $("#city_id").val(), {
            pc_lat: $("#city_lat").val(),
            pc_lon: $("#city_lon").val(),
            pc_zoom: $("#city_zoom").val()
        },
        function (a) {
            if (a) {
                $("#pc_latitude").val($("#city_lat").val());
                $("#pc_longitude").val($("#city_lon").val());
                $.modal.close();
            }
        })
    });
    $("#pc_latlon_handler input.doesc").live("click", function () {//------------ escape
        $.modal.close();
    });
    //---------------------------------- / CITY GPS ----------------------------------
    //----------------------------------- POINTS FILTER ------------------------------
    $("a.typefilterlink").live("click", function () {//----- фильтр достопримечательностей по типу
        var a = this.href.split("#")[1].split("_")[1];
        $('a.points_selector_active').removeClass("points_selector_active").addClass("points_selector_inactive");
        $(this).removeClass("points_selector_inactive").addClass("points_selector_active");
        $(".points_selector_active").each(function () {
            $(this).children("img").attr("src", $(this).children("img").attr("src").replace("x32", "i32"));
        });
        $(".points_selector_inactive").each(function () {
            $(this).children("img").attr("src", $(this).children("img").attr("src").replace("i32", "x32"));
        });
        if ($(this).parent('li').parent('ul').attr('id') == "menu_type2") {
            if (a != "all") {
                $("#whatservlist tr").hide();
                $("#whatservlist tr.obj_type_" + a).show();
            } else
                $("#whatservlist tr").show();
        }
        if ($(this).parent('li').parent('ul').attr('id') == "menu_type1") {
            if (a != "all") {
                $("#whatseelist tr").hide();
                $("#whatseelist tr.obj_type_" + a).show();
            } else
                $("#whatseelist tr").show();
        }
        //return false;
    });
    //--------------------------------- / POINTS FILTER ------------------------------
    //------------------------------------ BLOG AJAX ---------------------------------
    $(".blog_entry_edit").live("click", function () {
        var a = this.id.split("_");
        showWindByURL("/blog/editform/", {
            brid: a[2]
        });
        $("body").live("afterShowWindByURL", function () {
            $("#body").die("afterShowWindByURL");
            $("#eblog_text").ckeditor({
                customConfig: '/config/config.cke4.js',
                height: '250px',
                toolbar: 'Lite'
            });
            $("#eblog_date").datepicker({
                dateFormat: "dd.mm.yy"
            });
        });
        $("#eblog_date").live("click", function () {
            $(this).datepicker({
                dateFormat: "dd.mm.yy"
            });
        });
        return false;
    });
    $("#blog_entry_add").live("click", function () {
        showWindByURL("/blog/addform/", null);
        $("#eblog_text").live("mouseover", function () {
            $("#eblog_text").die("mouseover").ckeditor(function () {
            }, {
                customConfig: "/config/config.cke4.js",
                height: '250px',
                toolbar: "Lite"
            });
            $("#eblog_date").datepicker({
                dateFormat: "dd.mm.yy"
            });
        });
        $("#eblog_date").live("click", function () {
            $(this).datepicker({
                dateFormat: "dd.mm.yy"
            });
        });
        return false;
    });
    $(".blog_entry_delete").live("click", function () {
        var a = this.id.split("_");
        confirm('Действительно удалить запись "' + $(this).parent('h2').children('a').text() + '"?') && $.post("/blog/delentry/?bid=" + a[2], {
            brid: a[2]
        }, function (g) {
            if (g) {
                document.location = "/blog/";
            }
        });
        return false;
    });
//--------------------------------- / BLOG AJAX ----------------------------------
//---------------------------------  CITY AJAX  ----------------------------------
    if ($("#city_keywds").val())
        $("#city_sign_keywds").text($("#city_keywds").val().length);
    $("#city_sign_descr").text($("#city_descr").text().length);
    $(document).on("keyup", "#city_keywds", function () {
        $("#city_sign_keywds").text($("#city_keywds").val().length);
    });
    $(document).on("keyup", "#city_descr", function () {
        $("#city_sign_descr").text($("#city_descr").val().length);
    });
    $(document).on("keyup", "#city_anons", function () {
        $("#city_sign_anons").text($("#city_anons").val().length);
    });
    $(".button_active").click(function () {
        switch ($(this).data("act")) {
            case "add":
                if ($("#city_meta_add_value").val() !== "") {
                    $.post("/city/meta/", {
                        act: $(this).data("act"),
                        cf: $("#city_meta_add_cf").val(),
                        val: $("#city_meta_add_value").val(),
                        cpid: $("#city_meta_pcid").val()
                    }, function (data) {
                        if (data !== "") {
                            $("#city_meta_add_value").val("");
                            $("#city_meta_add_cf :selected").remove();
                            //$("#city_meta_table").append("<tr><td>" + data + "</td><td>" + $("#city_meta_add_value").val() + "</td><td></td><td>");
                        }
                    });
                }
                break;
            case "del":
                var cf = $(this).data("cfid");
                $.post("/city/meta/", {
                    act: $(this).data("act"),
                    cf: cf,
                    cpid: $("#city_meta_pcid").val()
                }, function (data) {
                    if (data === "ok") {
                        $("#city_meta_row_" + cf).hide();
                    }
                });
                break;
            case "edit":
                var cf = $(this).data("cfid");
                $.post("/city/meta/", {
                    act: $(this).data("act"),
                    cf: cf,
                    val: $("#city_meta_value_" + cf).val(),
                    cpid: $("#city_meta_pcid").val()
                });
                break;
        }
    });
//--------------------------------- / CITY AJAX ----------------------------------
//----------------------------   SEARCH AUTOCOMPLETE  ----------------------------
    $('#searchform_input').autocomplete({
        serviceUrl: "/search/suggest/",
        paramName: "query",
        onSelect: function (suggestion) {
            document.location.href = suggestion.url;
        }
    });
    $('#search_mainform_q').autocomplete({
        serviceUrl: "/search/suggest/",
        paramName: "query",
        onSelect: function (suggestion) {
            document.location.href = suggestion.url;
        }
    });
//---------------------------- / SEARCH AUTOCOMPLETE  ----------------------------
//---------------------------------   AUTH  --------------------------------------
    $("#show_auth_form").click(function () {
        showWindByURL("/sign/form/", {});
        return false;
    });

    if (window.location.hash) {
        var matches = /^#type_([0-9]+)/.exec(window.location.hash);
        if (matches) {
            var typeid = parseInt(matches[1]);
            $("#type_selector_" + typeid).click();
        }
    }


    //---------------------- SCROLL TO TOP ---------------------------------------
    if ($("#mapcity_pc_id").val() > 0) { //пока только на страницах регионов
        var $scrollerTopButton = $('.content-scroll-buttons');
        var scrollerTopOffsetTrigger = $('#menu_type1').position().top + $('#menu_type1').outerHeight(true); //px to show button
        $(window).scroll(function () {
            if ($(this).scrollTop() > scrollerTopOffsetTrigger) {
                $scrollerTopButton.stop().animate({
                    top: '50%'
                }, 500);
            }
            else {
                $scrollerTopButton.stop().animate({
                    top: '-100px'
                }, 500);
            }
        });
        $scrollerTopButton.click(function () {
            $('html, body').stop().animate({
                scrollTop: $('h2:nth-of-type(1)').position().top
            }, 500, function () {
                $scrollerTopButton.stop().animate({
                    top: '-100px'
                }, 500);
            });
        });
    }
});



//======================= FUNCTIONS ==============================================
function showWindByURL(url, get) {
    //*функция показа модального окна с контентом по URL*/
    var xdata = '<div style="text-align:center;height:200px;padding-top:100px;color:#5478E4"><img src="/img/preloader/horizontal.gif" /><br/>загрузка</div>';
    $.modal(xdata, {
        overlayClose: true,
        opacity: 80,
        width: 600,
        height: 200,
        overlayCss: {
            backgroundColor: "#ddd"
        },
        onShow: function (dialog) {//перепозиционирование окна после показа
            var modal = this;
            modal.setPosition();
        }
    });
    $.get(url, get, function (data) {
        $("#simplemodal-data").html(data);
        if ($("#object_container h2").position() !== undefined) {
            $("#object_text_container").css("bottom", $("#object_additional").height())
                    .css("top", 2.7 * ($("#object_container h2").position().top + $("#object_container h2").height()));
        }
        $("body").trigger('afterShowWindByURL');
    });
}

function showMap(c_lat, c_lon, c_zoom, f_point) {
    ymaps.ready(function () {
        var map = new ymaps.Map("objfinder_map", {
            center: [c_lon, c_lat],
            zoom: c_zoom,
            type: "yandex#publicMap",
            controls: ["zoomControl", "typeSelector"]
        });
        if (f_point == -1) {//от клика
            map.events.add('click', function (e) {
                var coords = e.get('coords');
                $("#obj_lat").val(coords[1]);
                $("#obj_lon").val(coords[0]);
                $("#obj_zoom").val(map.getZoom());
                $("#city_lat").val(coords[1]);
                $("#city_lon").val(coords[0]);
                $("#city_zoom").val(map.getZoom());
            });
        }
        else if (f_point == 0) {//от координат города
            var mapOnClick = function (e) {
                map.events.remove("click", mapOnClick);
                var coords = e.get('coords');
                myPlacemark = new ymaps.Placemark(coords, {
                    hintContent: "Перетащите для изменения координат",
                    balloonContent: $("#obj_name").text()
                }, {
                    iconLayout: 'default#image',
                    iconImageHref: '/img/points/xmap/' + $('#obj_typeicon_h').val(), // картинка иконки
                    iconImageSize: [55, 55], // размеры картинки
                    iconImageOffset: [-27, -55], // смещение картинки
                    draggable: true // Метку можно перетаскивать, зажав левую кнопку мыши.
                });
                $("#obj_lat").val(coords[1]);
                $("#obj_lon").val(coords[0]);
                $("#obj_zoom").val(map.getZoom());
                $("#city_lat").val(coords[1]);
                $("#city_lon").val(coords[0]);
                $("#city_zoom").val(map.getZoom());
                myPlacemark.events.add("dragend", function () {
                    coords = myPlacemark.geometry.getCoordinates();
                    $("#obj_lat").val(coords[1]);
                    $("#obj_lon").val(coords[0]);
                    $("#obj_zoom").val(map.getZoom());
                    $("#city_lat").val(coords[1]);
                    $("#city_lon").val(coords[0]);
                    $("#city_zoom").val(map.getZoom());
                });
                map.geoObjects.add(myPlacemark);
            };
            map.events.add('click', mapOnClick);
        }
        else if (f_point == 1) {//от имеющихся координат объекта
            myPlacemark = new ymaps.Placemark([c_lon, c_lat], {
                hintContent: "Перетащите для изменения координат",
                balloonContent: $("#obj_name").text()
            }, {
                iconLayout: 'default#image',
                iconImageHref: '/img/points/xmap/' + $('#obj_typeicon_h').val(), // картинка иконки
                iconImageSize: [55, 55], // размеры картинки
                iconImageOffset: [-27, -55], // смещение картинки
                draggable: true // Метку можно перетаскивать, зажав левую кнопку мыши.
            });
            myPlacemark.events.add("dragend", function () {
                coords = myPlacemark.geometry.getCoordinates();
                $("#obj_lat").val(coords[1]);
                $("#obj_lon").val(coords[0]);
                $("#obj_zoom").val(map.getZoom());
                $("#city_lat").val(coords[1]);
                $("#city_lon").val(coords[0]);
                $("#city_zoom").val(map.getZoom());
            });
            map.geoObjects.add(myPlacemark);
        }

        //поиск координат по адресу
        $(".dogo").live("click", function () {
            var point = [parseFloat($("#obj_lon").val()), parseFloat($("#obj_lat").val())];
            map.panTo(point, {
                flying: true,
                delay: 0,
                duration: 1000
            });
            return false;
        });

        //поиск адреса по координатам
        $(".doreverse").live("click", function () {
            ymaps.geocode([parseFloat($("#obj_lon").val()), parseFloat($("#obj_lat").val())], {
                kind: 'house',
                json: true,
                provider: 'yandex#map',
                results: 1
            }).then(function (res) {
                var variant = res.GeoObjectCollection.featureMember[0].GeoObject;
                var addrLocal = variant.name;
                var addrCity = variant.metaDataProperty.GeocoderMetaData.text;
                if (addrCity) {
                    $("#obj_addr_searcher").text(addrCity);
                    $(".savereverse").show();
                }
            });
        });
        $(".savereverse").live("click", function () {
            $.post("/ajax/point/saveAddrGPS/?pid=" + $("#obj_id").val(), {
                id: $("#obj_id").val(),
                addr: $("#obj_addr_searcher").text()
            },
            function (resp) {
                if (resp) {
                    $(".savereverse").hide();
                }
            });
        });


        $("#obj_addr_searcher").live("click", function () {
            ymaps.geocode($("#obj_addr_searcher").text(), {
                kind: 'house',
                boundedBy: map.getBounds(),
                results: 20
            }).then(function (res) {
                map.geoObjects.add(res.geoObjects);
                res.geoObjects.each(function (obj) {
                    map.panTo(obj.geometry.getCoordinates(), {
                        flying: true,
                        delay: 0,
                        duration: 1000
                    });
                });
            });
        });
    });
}

