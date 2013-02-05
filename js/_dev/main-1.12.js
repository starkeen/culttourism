$(document).ready(function() {
    var BASEURL = 'http://' + window.location.host + '/';
    $('#loginform').load(BASEURL + 'ajax/forms/commonlogin/');
    $('#logintoggler').toggle(function(){
        $('#logintoggler').animate({
            top:'28px'
        });
        $('#loginform').animate({
            height:'24px'
        });
        this.src = BASEURL + 'img/btn/btn.grip.png';
    }, function(){
        $('#logintoggler').animate({
            top:'3px'
        });
        $('#loginform').animate({
            height:'0px'
        });
        this.src = BASEURL + 'img/btn/btn.expand.png';
    });
    
    //---------------------------------- OPEN OBJECT WINDOW ----------------------------
    $('.objlink').live('click', function(){
        var href = this.href.split('/').pop();
        var gett = {
            id:href.slice(6, -5)
        };
        showWindByURL(BASEURL+'ajax/point/', gett);
        return false;
    });
    //=============================---------------------- CITY EDIT ------------------------
    $('.hiddenedit').live('click',function(){
        $(this).addClass('hiddenedit_active');
        if (this.id == 'pc_title_edit') $('div#pc_title_handler input').show();
        if (this.id == 'pc_text_edit') $('div#pc_text_handler input').show();
        if (this.id == 'pc_text_hidd') {//------------- текст о городе --
            $(document).css('cursor', 'progress');
            $('#pc_text_edit').ckeditor(function() {
                //
                }, {
                    customConfig : '../../../../config/config.cke.js',
                    toolbar: 'City'
                });
            $('#pc_text_edit').css('height', '500px');
            $('#pc_text_edit').val($(this).html());
            $(this).hide();
            $('#pc_text_handler input').show();
            $('#map_container').hide();
            $(document).css('cursor', 'default');
        }
        if (this.id == 'pt_name_hidd') {//-------- название точки ------
            $(this).hide();
            $('#pt_name_edit').show().focus().addClass('hiddenedit_active').val($(this).html());
            $('div#pt_name_handler input').show();
        }
        if (this.id == 'pt_description_hidd') {//---------- описание точки -----
            $(document).css('cursor', 'progress');
            $('#pt_description_edit').css('height','300px');
            $('#pt_description_edit').css('width','100%');
            $('#pt_description_edit').val($(this).html());
            $('#pt_description_edit').ckeditor(function() {
                //
                }, {
                    customConfig : '../../../../config/config.cke.js'
                });
            $(this).hide();
            $('#pt_description_edit').show().focus();
            $('#pt_description_edit').css('height','0');
            $('div#pt_description_handler input').show();
            $(document).css('cursor', 'default');
        }
    });
    //------------------------ SAVE EDIT FIELD ------------
    $('.formhandler input.dosave').live('click',function(){
        if ($(this).parent().attr('id') == 'pc_title_handler') {//--- название города
            $(document).css('cursor', 'progress');
            $('#pc_title_handler input').hide();
            $.post(BASEURL + 'ajax/city/savetitle/?id='+$('#pc_id').val(),
            {
                id:$('#pc_id').val(),
                ntitle:$('#pc_title_edit').val()
            },
            function(data) {
                $('div#pc_title_handler input').hide();
                $('#pc_title_edit').removeClass('hiddenedit_active');
                $('#pc_title_edit').val(data);
            });
            $(document).css('cursor', 'default');
        }
        if ($(this).parent().attr('id') == 'pc_text_handler') {//--- описание города
            $(document).css('cursor', 'progress');
            $('#pc_text_handler input').hide();
            $.post(BASEURL+'ajax/city/savedescr/?id='+$('#pc_id').val(),
            {
                id:$('#pc_id').val(),
                ntext:$('#pc_text_edit').val()
            },
            function(data) {
                $('#pc_text_edit').ckeditor(function(){
                    this.destroy();
                });
                $('#pc_text_edit').css('height', '0');
                $('#pc_text_edit').removeClass('hiddenedit_active').hide();
                $('#pc_text_hidd').html(data).show().removeClass('hiddenedit_active');
                $('#map_container').show();
            });
            $(document).css('cursor', 'default');
        }
        if ($(this).parent().attr('id') == 'pt_name_handler') {//--- название точки
            $(document).css('cursor', 'progress');
            $('#pt_name_handler input').hide();
            $.post(BASEURL+'ajax/point/savetitle/?id='+$('#pt_id').val(),
            {
                id:$('#pt_id').val(),
                nname:$('#pt_name_edit').val()
            },
            function(data) {
                $('div#pt_name_handler input').hide();
                $('#pt_name_edit').removeClass('hiddenedit_active').hide();
                $('#pt_name_hidd').text(data);
                $('#pt_name_hidd').show().removeClass('hiddenedit_active');
                $('#object_id_' + $('#pt_id').val()).text(data);
            });
            $(document).css('cursor', 'default');
        }
        if ($(this).parent().attr('id') == 'pt_description_handler') {//--- описание точки
            $('div#pt_description_handler input').hide();
            $(document).css('cursor', 'progress');
            $.post(BASEURL + 'ajax/point/savedescr/?id='+$('#pt_id').val(),
            {
                id:$('#pt_id').val(),
                ndesc:$('#pt_description_edit').val()
            },
            function(data) {
                $('#pt_description_edit').ckeditor(function(){
                    this.destroy();
                });
                $('#pt_description_edit').css('height', '0');
                $('#pt_description_edit').removeClass('hiddenedit_active').hide();
                $('#pt_description_hidd').html(data).show().removeClass('hiddenedit_active');
            });
            $(document).css('cursor', 'default');
        }
        if ($(this).parent().attr('id') == 'pt_add_handler') {//--- добавление точки
            $(document).css('cursor', 'progress');
            $('div#pt_add_handler input').hide();
            $.post(BASEURL + 'ajax/point/savenew/?cid='+$('#pc_id').val(),
            {
                cid:$('#pc_id').val(),
                nname:$('#pt_name_add').val(),
                ndesc:$('#pt_description_add').val(),
                nweb:$('#pt_web_add').val(),
                nmail:$('#pt_email_add').val(),
                nphone:$('#pt_phone_add').val(),
                nwork:$('#pt_worktime_add').val(),
                naddr:$('#pt_addr_add').val()
            },
            function(data) {
                $('#pt_description_add').ckeditor(function(){
                    this.destroy();
                });
                $('#whatseelist').append('<tr><td><img class="point_typer" id="type_'+data+'" src="../../../img/points/32/star.png" alt="другое" /></td><td><a href="object'+data+'.html" id="object_id_'+data+'" class="objlink" title="подробно: '+$('#pt_name_add').val()+'">'+$('#pt_name_add').val()+'</a></td><td><a href="#" id="gps_'+data+'" class="point_latlon">указать</a></td><td><img class="point_deleter" id="del_'+data+'" src="../../../img/btn/ico.delete.gif" /></td></tr>');
                $.modal.close();
            });
            $(document).css('cursor', 'default');
        }
        if ($(this).parent().attr('id') == 'pt_contacts_handler') {//--- сохранение контактов
            $(document).css('cursor', 'progress');
            $('#pt_contacts_handler input').hide();
            $('#pt_cont_adress').text($('#pt_cont_adress_edit').val());
            $('#pt_cont_worktime').text($('#pt_cont_worktime_edit').val());
            $('#pt_cont_phone').text($('#pt_cont_phone_edit').val());
            $('#pt_cont_website').text($('#pt_cont_website_edit').val());
            $('#pt_cont_website').attr('href', $('#pt_cont_website_edit').val());
            $('#pt_cont_email').text($('#pt_cont_email_edit').val());
            $('#pt_cont_email').attr('href', 'mailto:'+$('#pt_cont_email_edit').val());
            $.post(BASEURL + 'ajax/point/savecontacts/?cid='+$('#pt_id').val(),
            {
                cid:$('#pt_id').val(),
                nwebsite:$('#pt_cont_website').text(),
                nemail:$('#pt_cont_email').text(),
                nphone:$('#pt_cont_phone').text(),
                nworktime:$('#pt_cont_worktime').text(),
                nadress:$('#pt_cont_adress').text()
            },
            function(data) {
                if (data) {
                    $('.edit_cont').show();
                    $('div#pt_contacts_handler input').hide();
                    $('.hiddenedit_cont').hide();
                    $('#do_cont_edit').show();
                }
            });
            $(document).css('cursor', 'default');
        }
        if ($(this).parent().attr('id') == 'br_save_handler') {//------ запись в блоге
            $.post(BASEURL + 'ajax/blog/saveform/?bid='+$('#pr_id').val(),
            {
                brid:$('#br_id').val(),
                ntitle:$('#eblog_title').val(),
                ntext:$('#eblog_text').val(),
                ndate:$('#eblog_date').val(),
                ntime:$('#eblog_time').val(),
                nact:$('#eblog_active').attr('checked'),
                nurl:$('#eblog_url').val()
            },
            function(data) {
                if (data) {
                    $('#eblog_text').ckeditor(function(){
                        this.destroy();
                    });
                    $.modal.close();
                    document.location = '../../blog/';
                }
            });
        }
    });
    //------------------------ ESCAPE EDIT FIELD ------------
    $('.formhandler input.doesc').live('click',function(){
        if ($(this).parent().attr('id') == 'pc_title_handler') {
            $('#pc_title_edit').val($('#pc_title_hidd').val()).removeClass('hiddenedit_active');
            $('div#pc_title_handler input').hide();
        }
        if ($(this).parent().attr('id') == 'pc_text_handler') {//------ описание города
            $('#pc_text_edit').ckeditor(function(){
                this.destroy();
            });
            $('#pc_text_edit').css('height', '0');
            $('#pc_text_edit').hide();
            $('#pc_text_hidd').show().removeClass('hiddenedit_active');
            $('div#pc_text_handler input').hide();
            $('#map_container').show();
        }
        if ($(this).parent().attr('id') == 'pt_name_handler') {//------ имя точки
            $('#pt_name_edit').hide().removeClass('hiddenedit_active');
            $('#pt_name_hidd').show().removeClass('hiddenedit_active');
            $('div#pt_name_handler input').hide();
        }
        if ($(this).parent().attr('id') == 'pt_description_handler') {//------ описание точки
            $('#pt_description_edit').ckeditor(function(){
                this.destroy();
            });
            $('#pt_description_edit').hide().removeClass('hiddenedit_active');
            $('#pt_description_hidd').show().removeClass('hiddenedit_active');
            $('#pt_description_handler input').hide();
        }
        if ($(this).parent().attr('id') == 'pt_add_handler') {//------ добавление точки
            $('#pt_description_add').ckeditor(function(){
                this.destroy();
            });
            $('#pt_description_add').live('mouseover', function(){
                $('#pt_description_add').die('mouseover');
                $('#pt_description_add').ckeditor(function() {
                    //
                    }, {
                        customConfig : '../../../../config/config.cke.js'
                    });

            });
            $.modal.close();
        }
        if ($(this).parent().attr('id') == 'pt_contacts_handler') {//------ сохранение контактов
            $('.edit_cont').show();
            $('div#pt_contacts_handler input').hide();
            $('.hiddenedit_cont').hide();
            $('#do_cont_edit').show();
        }
        if ($(this).parent().attr('id') == 'br_save_handler') {//------ запись в блоге
            $('#eblog_text').ckeditor(function(){
                this.destroy();
            });
            $.modal.close();
        }
    });
    //-------------------------------- / CITY EDIT -----------------------------------
    //--------------------------------- POINT CONTACTS -------------------------------
    $('#do_cont_edit').live('click', function() {
        $('#pt_cont_adress_edit').val($('#pt_cont_adress').text());
        $('#pt_cont_worktime_edit').val($('#pt_cont_worktime').text());
        $('#pt_cont_phone_edit').val($('#pt_cont_phone').text());
        $('#pt_cont_website_edit').val($('#pt_cont_website').text());
        $('#pt_cont_email_edit').val($('#pt_cont_email').text());
        $('div#pt_contacts_handler input').show();
        $(this).hide();
        $('.edit_cont').hide();
        $('.hiddenedit_cont').show();
    });
    //----------------------------- / POINT CONTACTS ---------------------------------
    //--------------------------------- POINT ADD ------------------------------------
    $('#do_add_point').click(function(){
        $(document).css('cursor', 'progress');
        var gett = {
            cid:$('#pc_id').val()
        };
        showWindByURL(BASEURL+'ajax/point/getnewform/', gett);
        $('#pt_description_add').live('mouseover', function(){
            $('#pt_description_add').die('mouseover');
            $('#pt_description_add').ckeditor(function() {
                //
                }, {
                    customConfig : '../../../../config/config.cke.js'
                });
        });
        $(document).css('cursor', 'default');
        return false;
    });
    //---------------------------------------- /  POINT ADD --------------------------
    //------------------------------------------- POINT DEL --------------------------
    $('.point_deleter').live('click',function(){
        var ptid = this.id.split('_');
        var tablerow = $(this).parents('tr');
        if (confirm('Действительно удалить точку?')) {
            $.post(
                BASEURL + 'ajax/point/delpoint/?pid=' + ptid[1], {
                    pid:ptid[1]
                },
                function(data){
                    if (data) {
                        $(tablerow).remove();
                    }
                });
        }
    });
    //---------------------------------------- /  POINT DEL --------------------------
    //------------------------------------------ POINT TYPE --------------------------
    $('.point_typer').live('click',function(){
        var ptid = this.id.split('_');
        var gett = {
            pid:ptid[1]
        };
        showWindByURL(BASEURL+'ajax/pointtype/getform/', gett);
    });
    $('#type_selector tr').live('click',function(){
        var ntypea = this.id.split('_');
        var point_id = $('#pt_id').val();
        $.post(BASEURL + 'ajax/pointtype/savetype/?pid='+$('#pt_id').val(),
        {
            pid:point_id,
            ntype:ntypea[1]
        },
        function(data) {
            $('img#type_'+point_id+'.point_typer').attr('src', '../../../img/points/32/'+data);
            $.modal.close();
        });
    });
    //--------------------------------------- /  POINT TYPE --------------------------
    //------------------------------------------ FEEDBACK --------------------------
    $('#captchahelp').click(function(){
        stamp = new Date();
        $('#norobotpic').attr('src','../../../feedback/getcapt/' + stamp.getTime());
        return false;
    });
    //--------------------------------------- POINT GPS ------------------------------
    $('.point_latlon').live('click', function(){
        var ptid = this.id.split('_');
        var gett = {
            pid:ptid[1]
        };
        showWindByURL(BASEURL+'ajax/point/getformGPS/', gett);
        return false;
    });
    $('#pt_latlon_handler input.dosave').live('click',function(){//----------- save
        $.post(
            BASEURL + 'ajax/point/saveformGPS/?pid=' + $('#obj_id').val(), {
                pt_lat:$('#obj_lat').val(),
                pt_lon:$('#obj_lon').val(),
                pt_zoom:$('#obj_zoom').val()
            },
            function(data){
                if (data) {
                    $('#gps_'+$('#obj_id').val()).text(data);
                    $.modal.close();
                }
            });
    });
    $('#pt_latlon_handler input.doesc').live('click',function(){//------------ escape
        $.modal.close();
    });
    //------------------------------------- / POINT GPS ------------------------------
    //------------------------------------ CITY GPS ----------------------------------
    $('#citymap_finder').click(function(){
        var cidarr = document.location.search.split('city_id=');
        var gett = {
            cid:cidarr[1]
        };
        showWindByURL(BASEURL+'ajax/city/getformGPS/', gett);
        return false;
    });
    $('#pc_latlon_handler input.dosave').live('click',function(){//----------- save
        $.post(
            BASEURL + 'ajax/city/saveformGPS/?cid=' + $('#city_id').val(), {
                pc_lat:$('#city_lat').val(),
                pc_lon:$('#city_lon').val(),
                pc_zoom:$('#city_zoom').val()
            },
            function(data){
                if (data) {
                    $('#pc_latitude').val($('#city_lat').val());
                    $('#pc_longitude').val($('#city_lon').val());
                    $.modal.close();
                }
            });
    });
    $('#pc_latlon_handler input.doesc').live('click',function(){//------------ escape
        $.modal.close();
    });
    //---------------------------------- / CITY GPS ----------------------------------
    //----------------------------------- POINTS FILTER ------------------------------
    $('a.typefilterlink').live('click',function(){//----- фильтр достопримечательностей по типу
        var hrefs = this.href.split('#');
        var types = hrefs[1].split('_');
        var filter_type = types[1];
        if (filter_type != 'all') {
            $('#whatseelist tr').hide();
            $('#whatseelist tr.obj_type_' + filter_type).show();
        }
        else {
            $('#whatseelist tr').show();
        }
        return false;
    });
    $('a.typefilterlink0').live('click',function(){//----- фильтр вторички по типу
        var hrefs = this.href.split('#');
        var types = hrefs[1].split('_');
        var filter_type = types[1];
        if (filter_type != 'all') {
            $('#whatservlist tr').hide();
            $('#whatservlist tr.obj_type_' + filter_type).show();
        }
        else {
            $('#whatservlist tr').show();
        }
        return false;
    });
    //--------------------------------- / POINTS FILTER ------------------------------
    //------------------------------------ BLOG AJAX ---------------------------------
    $('.blog_entry_edit').live('click', function(){
        var abrid = this.id.split('_');
        showWindByURL(BASEURL + 'ajax/blog/editform/', {
            brid:abrid[2]
        });
        $('#eblog_text').live('mouseover', function(){
            $('#eblog_text').die('mouseover');
            $('#eblog_text').ckeditor(function() {
                }, {
                    customConfig : '../../../../config/config.cke.js'
                });
            $('#eblog_date').datepicker({
                dateFormat: 'dd.mm.yy'
            });
        });
        $('#eblog_date').live('click', function(){
            $(this).datepicker({
                dateFormat: 'dd.mm.yy'
            });
        });
        return false;
    });
    $('#blog_entry_add').live('click', function(){
        showWindByURL(BASEURL + 'ajax/blog/addform/', null);
        $('#eblog_text').live('mouseover', function(){
            $('#eblog_text').die('mouseover');
            $('#eblog_text').ckeditor(function() {
                }, {
                    customConfig : '../../../../config/config.cke.js'
                });
            $('#eblog_date').datepicker({
                dateFormat: 'dd.mm.yy'
            });
        });
        $('#eblog_date').live('click', function(){
            $(this).datepicker({
                dateFormat: 'dd.mm.yy'
            });
        });
        return false;
    });
    $('.blog_entry_delete').live('click', function(){
        var abrid = this.id.split('_');
        if (confirm('Действительно удалить запись "'+$(this).parent('h2').children('a').text()+'"?'))
            $.post(BASEURL + 'ajax/blog/delentry/?bid='+abrid[2],
            {
                brid:abrid[2]
            },
            function(data) {
                if (data) {
                    document.location = '../../blog/';
                }
            });
        return false;
    });
//--------------------------------- / BLOG AJAX ----------------------------------
});

//======================= FUNCTIONS ==============================================
function showWindByURL(url, get) {
    /*функция показа модального окна с контентом по URL*/
    $(document).css('cursor', 'progress');
    $.get(
        url, get,
        function(data){
            $.modal(data, {
                overlayClose:true,
                opacity:80,
                width:600,
                height:200,
                overlayCss: {
                    backgroundColor:"#ddd"
                }
            });
        });
    $(document).css('cursor', 'default');
}

function showMap(c_lat, c_lon, c_zoom, f_point) {
    $(document).css('cursor', 'progress');
    var map = new YMaps.Map(YMaps.jQuery("#objfinder_map")[0]);
    map.setCenter(new YMaps.GeoPoint(c_lon, c_lat), c_zoom);
    map.addControl(new YMaps.TypeControl([YMaps.MapType.MAP, YMaps.MapType.HYBRID], [1,1]));
    map.addControl(new YMaps.Zoom());
    map.addControl(new YMaps.ScaleLine());
    map.enableScrollZoom();

    if (f_point == 1) {
        var placemark = new YMaps.Placemark(new YMaps.GeoPoint(c_lon, c_lat), {
            draggable:true
        });
        map.addOverlay(placemark);
        YMaps.Events.observe(placemark, placemark.Events.Drag, function (obj) {
            obj.setIconContent($('#obj_name').text());
        });
        YMaps.Events.observe(placemark, placemark.Events.DragEnd, function (obj) {
            placemark.name = $('#obj_name').text();
            placemark.openBalloon();
            obj.setIconContent(null);
            obj.update();
            var newlatlon = obj.getGeoPoint();
            $('#obj_lat').val(newlatlon.getLat());
            $('#obj_lon').val(newlatlon.getLng());
            $('#obj_zoom').val(map.getZoom());
            $('#city_lat').val(newlatlon.getLat());
            $('#city_lon').val(newlatlon.getLng());
            $('#city_zoom').val(map.getZoom());
        });
    }
    if (f_point == 0) {
        var myEventListener = YMaps.Events.observe(map, map.Events.Click, function (map, mEvent) {
            var placemark = new YMaps.Placemark(mEvent.getGeoPoint(), {
                draggable:true
            });
            map.addOverlay(placemark);
            myEventListener.cleanup();
            var newlatlon = mEvent.getGeoPoint();
            $('#obj_lat').val(newlatlon.getLat());
            $('#obj_lon').val(newlatlon.getLng());
            $('#city_lat').val(newlatlon.getLat());
            $('#city_lon').val(newlatlon.getLng());
            YMaps.Events.observe(placemark, placemark.Events.Drag, function (obj) {
                if ($('#obj_name').text() != '') obj.setIconContent($('#obj_name').text());
                if ($('#city_name').text() != '') obj.setIconContent($('#city_name').text());
            });
            YMaps.Events.observe(placemark, placemark.Events.DragEnd, function (obj) {
                if ($('#obj_name').text() != '') placemark.name = $('#obj_name').text();
                if ($('#city_name').text() != '') placemark.name = $('#city_name').text();
                placemark.openBalloon();
                obj.setIconContent(null);
                obj.update();
                var newlatlon = obj.getGeoPoint();
                $('#obj_lat').val(newlatlon.getLat());
                $('#obj_lon').val(newlatlon.getLng());
                $('#obj_zoom').val(map.getZoom());
                $('#city_lat').val(newlatlon.getLat());
                $('#city_lon').val(newlatlon.getLng());
                $('#city_zoom').val(map.getZoom());
            });
        }, this);
    }
    if (f_point == -1) {
        map.addControl(new YMaps.SearchControl());
        var myEventListener = YMaps.Events.observe(map, map.Events.Click, function (map, mEvent) {
            var placemark = new YMaps.Placemark(mEvent.getGeoPoint(), {
                draggable:true
            });
            var newlatlon = mEvent.getGeoPoint();
            $('#obj_lat').val(newlatlon.getLat());
            $('#obj_lon').val(newlatlon.getLng());
            $('#obj_zoom').val(map.getZoom());
            $('#city_lat').val(newlatlon.getLat());
            $('#city_lon').val(newlatlon.getLng());
            $('#city_zoom').val(map.getZoom());
            map.addOverlay(placemark);
            myEventListener.cleanup();
            YMaps.Events.observe(placemark, placemark.Events.DragEnd, function (obj) {
                if ($('#obj_name').text() != '') placemark.name = $('#obj_name').text();
                if ($('#city_name').text() != '') placemark.name = $('#city_name').text();
                placemark.openBalloon();
                obj.setIconContent(null);
                obj.update();
                var newlatlon = obj.getGeoPoint();
                $('#obj_lat').val(newlatlon.getLat());
                $('#obj_lon').val(newlatlon.getLng());
                $('#obj_zoom').val(map.getZoom());
                $('#city_lat').val(newlatlon.getLat());
                $('#city_lon').val(newlatlon.getLng());
                $('#city_zoom').val(map.getZoom());
            });
        }, this);
    }
    $(document).css('cursor', 'default');
}