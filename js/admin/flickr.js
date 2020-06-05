$(document).ready(function () {
    $("#flickr-import-button").on("click", function () {
        $("#flickr-import-console").text('');
        $("#flickr-import-preview").text('');
        $.getJSON('flickr.php', {
            act: "fetch",
            url: $("#flickr-import-url").val()
        }, function (response) {
            if (response.data.stat === "ok") {
                $("#flickr-import-photo-id").val(response.data.photo.id);
                $("#flickr-import-console")
                        .append('ID: ' + response.data.photo.id + '<br>')
                        .append('URL: ' + response.data.photo.urls.url[0]._content + '<br>')
                        .append('title: ' + response.data.photo.title._content + '<br>')
                        .append('license: ' + response.data.photo.license_text + '<br>')
                        .append('permissions: '
                                + 'blog:' + response.data.photo.usage.canblog + '; '
                                + 'download:' + response.data.photo.usage.candownload + '; '
                                + 'print:' + response.data.photo.usage.canprint + '; '
                                + 'share:' + response.data.photo.usage.canshare + '<br>')
                        .append('sizes: ' + response.sizes.sizes.size.length + '<br>');
                if (response.geo.stat === "ok") {
                    $("#flickr-import-console")
                            .append('Location: ' + response.geo.photo.location.locality._content + '<br>');
                }
                var img = new Image();
                img.src = response.sizes.sizes.size[1].source;
                $("#flickr-import-preview").append(img);
                $("#flickr-import-add").removeClass("m_hide");
            }
        });
    });

    $("#flickr-import-clean").click(function () {
        $("#flickr-import-url").val("");
        $("#flickr-import-photo-id").val(0);
        $("#flickr-import-console").text("");
    });

    $("#flickr-import-save-city-clean").click(function () {
        $("#flickr-import-city").val("");
        $("#flickr-import-city-id").val(0);
    });

    $("#flickr-import-save-object-clean").click(function () {
        $("#flickr-import-object").val("");
        $("#flickr-import-object-id").val(0);
    });

    $("#flickr-import-save").on("click", function () {
        $("#flickr-import-console").text("");
        $.post("flickr.php?act=save", {
            pcid: $("#flickr-import-city-id").val(),
            ptid: $("#flickr-import-object-id").val(),
            phid: $("#flickr-import-photo-id").val(),
            bindpc: $("#flickr-import-city-bind").attr("checked") ? 1 : 0,
            bindpt: $("#flickr-import-object-bind").attr("checked") ? 1 : 0
        }, function (response) {
            if (response.state) {
                $("#flickr-import-console").append("сохранено");
                $("#flickr-suggestions").dblclick();
            } else {
                $("#flickr-import-console").append("ошибка!");
            }
        });
    });

    $("#flickr-suggestions").on("dblclick", function () {
        $.get("flickr.php?act=suggestions", function (response) {
            $("#flickr-suggestions ul").empty();
            $.each(response.data, function (index, value) {
                $("#flickr-suggestions ul")
                        .append("<li>"
                                + value.pc_title_unique
                                + " (" + value.ws_weight_min + ")"
                                + "</li>");
            });
        });
    });
    $("#flickr-suggestions").dblclick();

    $("#flickr-objects-suggestions-refresh").on("click", function () {
        $.get("flickr.php?act=object_suggestions", function (response) {
            $("#flickr-objects-suggestions ul").empty();
            $.each(response.data, function (index, value) {
                let $element = $('<li>');
                $element.addClass("flickr-objects-suggestion");
                $element.text(value.pt_rank + " [" + value.pc_title_unique + "] " + value.pt_name);
                $element.data("id", value.pt_id);
                $element.data("region_id", value.pc_id);
                $element.data("region_name", value.pc_title_unique);
                $element.data("name", value.pt_name);
                $element.data("latitude", value.pt_latitude);
                $element.data("longitude", value.pt_longitude);
                $("#flickr-objects-suggestions ul").append($element);
            });
        });
    });
    $(".flickr-objects-suggestion").live("click", function (event) {
        let $item = $(event.target);
        console.log($item.data("id"));
        $("#flickr-search-points-suggest").val($item.data("name"));

        $("#flickr-search-points-id").val($item.data("id"));
        $("#flickr-search-points-latitude").val($item.data("latitude"));
        $("#flickr-search-points-longitude").val($item.data("longitude"));
        $("#flickr-search-points-go").prop("disabled", false);

        $("#flickr-search-city-suggest").val($item.data("region_name"));
        $("#flickr-search-city-id").val($item.data("region_id"));

        $("#flickr-import-url").val("");
        $("#flickr-import-photo-id").val("0");
        $("#flickr-import-console").text("");
        $("#flickr-import-preview").text("");
        $("#flickr-import-object").val($item.data("name"));
        $("#flickr-import-object-id").val($item.data("id"));
        $("#flickr-import-city").val($item.data("region_name"));
        $("#flickr-import-city-id").val($item.data("region_id"));
    });
    $("#flickr-objects-suggestions-refresh").click();


    $("#flickr-search-city-clean").on("click", function () {
        $("#flickr-search-city-suggest").val("");
        $("#flickr-search-city-id").val("0");
        $("#flickr-search-points-clean").click();
    });
    $("#flickr-search-points-clean").on("click", function () {
        $("#flickr-search-points-suggest").val("");
        $("#flickr-search-points-id").val("0");
        $("#flickr-search-points-latitude").val("0");
        $("#flickr-search-points-longitude").val("0");
        $("#flickr-search-points-go").prop("disabled", true);
    });
    $("#flickr-search-city-suggest").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 400,
        onSelect: function (suggestion) {
            $("#flickr-search-city-id").val(suggestion.data);
            $("#flickr-search-points-clean").click();
        }
    });
    $("#flickr-search-points-suggest").autocomplete({
        serviceUrl: "/search/suggest-object/",
        minChars: 3,
        paramName: "query",
        width: 400,
        transformResult: function (response) {
            let pc = $("#flickr-search-city-id").val();
            let resultSuggestions = [];
            $.map(response.suggestions, function (dataItem) {
                if (pc === "0" || dataItem.city_id.toString() === pc.toString()) {
                    resultSuggestions.push({
                        value: "[" + dataItem.city_title + "] " + dataItem.value,
                        data: dataItem.data,
                        latitude: dataItem.latitude,
                        longitude: dataItem.longitude
                    });
                }
            });
            return resultSuggestions;
        },
        onSelect: function (suggestion) {
            $("#flickr-search-points-id").val(suggestion.data);
            $("#flickr-search-points-latitude").val(suggestion.latitude);
            $("#flickr-search-points-longitude").val(suggestion.longitude);
            $("#flickr-search-points-go").prop("disabled", false);
        }
    });
    $("#flickr-search-points-go").on("click", function () {
        let latitude = $("#flickr-search-points-latitude").val();
        let longitude = $("#flickr-search-points-longitude").val();
        let url = 'https://www.flickr.com/map/?fLat=' + latitude + '&fLon=' + longitude + '&zl=16&everyone_nearby=1';
        let win = window.open(url, '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this website');
        }
    });


    $("#flickr-import-city").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 400,
        onSelect: function (suggestion) {
            $("#flickr-import-city-id").val(suggestion.data);
            $("#flickr-import-object").val("");
            $("#flickr-import-object-id").val("0");
        }
    });
    $("#flickr-import-object").autocomplete({
        serviceUrl: "/search/suggest-object/",
        minChars: 3,
        paramName: "query",
        width: 400,
        transformResult: function (response) {
            var pc = $("#flickr-import-city-id").val();
            var resultSuggestions = [];
            $.map(response.suggestions, function (dataItem) {
                if (pc === "0" || dataItem.city_id.toString() === pc.toString()) {
                    resultSuggestions.push({
                        value: "[" + dataItem.city_title + "] " + dataItem.value,
                        data: dataItem.data
                    });
                }
            });
            return resultSuggestions;
        },
        onSelect: function (suggestion) {
            $("#flickr-import-object-id").val(suggestion.data);
        }
    });
});
