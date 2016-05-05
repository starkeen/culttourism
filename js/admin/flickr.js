$(document).ready(function () {
    $("#flickr-import-button").on("click", function () {
        $("#flickr-import-console").text('');
        $.getJSON('flickr.php', {
            act: "fetch",
            url: $("#flickr-import-url").val()
        }, function (response) {
            if (response.data.stat == "ok") {
                $("#flickr-import-photo-id").val(response.data.photo.id);
                $("#flickr-import-console")
                        .append('ID: ' + response.data.photo.id + '<br>')
                        .append('URL: ' + response.data.photo.urls.url[0]._content + '<br>')
                        .append('title: ' + response.data.photo.title._content + '<br>');
                $("#flickr-import-add").removeClass("m_hide");
            }
        });
    });

    $("#flickr-import-save").on("click", function () {
        $("#flickr-import-console").text('');
        $.post("flickr.php?act=save", {
            pcid: $("#flickr-import-city-id").val(),
            phid: $("#flickr-import-photo-id").val()
        }, function (response) {
            if (response.state) {
                $("#flickr-import-console").append("сохранено");
            }
        });
    });

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