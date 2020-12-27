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
    //=============================

    //------------------------------------------ FEEDBACK --------------------------
    $("#captchahelp").click(function () {
        let stamp = new Date;
        $("#norobotpic").attr("src", "/feedback/getcapt/" + stamp.getTime());
        return false;
    });

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
        var $scrollerTopButton = $('.content-scroll-buttons').removeClass('m_hide');
        var scrollerTopOffsetTrigger = $('#menu_type1').position().top + $('#menu_type1').outerHeight(true); //px to show button
        $(window).scroll(function () {
            if ($(this).scrollTop() > scrollerTopOffsetTrigger) {
                $scrollerTopButton.stop().animate({
                    top: '50%'
                }, 500);
            } else {
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
    //* функция показа модального окна с контентом по URL */
    var xdata = '<div style="text-align:center;height:200px;padding-top:100px;color:#5478E4"><img src="/img/preloader/horizontal.gif" /><br/>загрузка</div>';
    $.modal(xdata, {
        overlayClose: true,
        opacity: 80,
        width: 600,
        height: 200,
        overlayCss: {
            backgroundColor: "#dddddd"
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

function reCaptchaCallback() {
    $('.g-recaptcha').parents('form').submit();
}
