$(document).ready(function () {
    $("#flickr-import-button").on("click", function () {
        $("#flickr-import-console").text('');
        $("#flickr-import-preview").text('');
        $.getJSON('flickr.php', {
            act: "fetch",
            url: $("#flickr-import-url").val()
        }, function (response) {
            if (response.data.stat == "ok") {
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
    });

    $("#flickr-import-save-clean").click(function () {
        $("#flickr-import-city").val("");
        $("#flickr-import-city-id").val(0);
        $("#flickr-import-photo-id").val(0);
    });

    $("#flickr-import-save").on("click", function () {
        $("#flickr-import-console").text('');
        $.post("flickr.php?act=save", {
            pcid: $("#flickr-import-city-id").val(),
            phid: $("#flickr-import-photo-id").val()
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


    $("#flickr-import-city").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 400,
        onSelect: function (suggestion) {
            $("#flickr-import-city-id").val(suggestion.data);
        }
    });
});