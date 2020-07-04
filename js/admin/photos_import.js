$(document).ready(function () {
    // обработка клика при нажатии кнопки "Обновить"
    $("#photos-objects-suggestions-refresh").on("click", function () {
        $.get("photos_import.php?act=suggest", function (response) {
            $("#photos-objects-suggestions ul").empty();
            $.each(response.data, function (index, value) {
                let $element = $('<li>');
                $element.addClass("photos-objects-suggestion");
                $element.text(value.pt_rank + " [" + value.pc_title_unique + "] " + value.pt_name);
                $element.data("id", value.pt_id);
                $element.data("region_id", value.pc_id);
                $element.data("region_name", value.pc_title_unique);
                $element.data("name", value.pt_name);
                $element.data("latitude", value.pt_latitude);
                $element.data("longitude", value.pt_longitude);
                $("#photos-objects-suggestions ul").append($element);
            });
        });
    }).click();

    // обработка клика по вариантам из саджеста
    $(".photos-objects-suggestion").live("click", function (event) {
        $("#photos-object-clear").click();
        let $element = $(event.target);
        $("#photos-object-detail-region").text($element.data("region_name"));
        $("#photos-object-detail-title").text($element.data("name"));
        $("#photos-object-detail-id").val($element.data("id"));
        $("#photos-object-detail-latitude").val($element.data("latitude"));
        $("#photos-object-detail-longitude").val($element.data("longitude"));
        $("#photos-object-search").show();
        $("#photos-object-clear").show();
        $("#photos-object-detail").show();
        $("#photos-object-go-buttons").show();
    });

    // переход на карту flickr
    $("#photos-object-go-flickr").live("click", function () {
        let latitude = $("#photos-object-detail-latitude").val();
        let longitude = $("#photos-object-detail-longitude").val();
        let url = 'https://www.flickr.com/map/?fLat=' + latitude + '&fLon=' + longitude + '&zl=16&everyone_nearby=1';
        let win = window.open(url, '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this website');
        }
    });

    // переход в поиск Яндекса
    $("#photos-object-go-yandex").live("click", function () {
        let regionName = $("#photos-object-detail-region").text();
        let objectName = $("#photos-object-detail-title").text();
        let query = regionName + ' ' + objectName;
        let url = 'https://yandex.ru/images/search?text=' + query;
        let win = window.open(url, '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this website');
        }
    });

    // переход в поиск Google
    $("#photos-object-go-google").live("click", function () {
        let regionName = $("#photos-object-detail-region").text();
        let objectName = $("#photos-object-detail-title").text();
        let query = regionName + ' ' + objectName;
        let url = 'https://www.google.com/search?q=' + query + '&tbm=isch';
        let win = window.open(url, '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this website');
        }
    });

    // Поиск готовых картинок
    $("#photos-object-search").live("click", function () {
        $("#photos-object-detail-results").empty();
        let regionName = $("#photos-object-detail-region").text();
        let objectName = $("#photos-object-detail-title").text();
        let query = regionName + ' ' + objectName;
        $.get(
            "photos_import.php",
            {
                q: query,
                act: "search"
            },
            function (response) {
                $.each(response.data, function (index, value) {
                    let $blockElement = $('<div>');
                    let $imgElement = $('<img>');
                    $blockElement.addClass("photos-object-detail-result-variant");
                    $imgElement.attr("src", value.url);
                    $imgElement.attr("alt", value.title);
                    $blockElement.append($imgElement);
                    $("#photos-object-detail-results").append($blockElement);
                });
            }
        );
    });

    // Очистка блока поиска
    $("#photos-object-clear").live("click", function () {
        $("#photos-object-detail-results").empty();
        $("#photos-object-detail-region").text("");
        $("#photos-object-detail-title").text("");
        $("#photos-object-detail-id").val("");
        $("#photos-object-detail-latitude").val("");
        $("#photos-object-detail-longitude").val("");
        $("#photos-object-search").hide();
        $("#photos-object-clear").hide();
        $("#photos-object-go-buttons").hide();
    });
});
