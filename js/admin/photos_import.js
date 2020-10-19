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
        $(".photos-objects-suggestion").removeClass("m_selected");
        $element.addClass("m_selected");
        $("#photos-object-detail-region").text($element.data("region_name"));
        $("#photos-object-detail-title").text($element.data("name"));
        $("#photos-object-detail-id").val($element.data("id"));
        $("#photos-object-detail-latitude").val($element.data("latitude"));
        $("#photos-object-detail-longitude").val($element.data("longitude"));
        $("#photos-object-detail").show();
        $("#photos-object-search").click();
    });

    // Поиск готовых картинок
    $("#photos-object-search").live("click", function () {
        $("#photos-object-detail-results").empty();
        $("#photos-object-detail-preview").empty();
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
                if (response.error_text) {
                    alert(response.error_text);
                }
                $.each(response.data, function (index, value) {
                    let $blockElement = $('<div>');
                    $blockElement.addClass("photos-object-detail-result-variant");

                    let $imgElement = $('<img>');
                    $imgElement.addClass("photos-object-detail-result-variant-img");
                    $imgElement.attr("src", value.thumbnailUrl);
                    $imgElement.attr("height", 100);
                    $imgElement.attr("alt", value.title);
                    $imgElement.data("url", value.url);
                    $imgElement.data("link", value.context);
                    $imgElement.data("title", value.title);
                    $blockElement.append($imgElement);

                    let $titleElement = $("<span>");
                    $titleElement.addClass("photos-object-detail-result-variant-title");
                    $titleElement.text(value.title);
                    $titleElement.attr("title", value.title);
                    $blockElement.append($titleElement);

                    let $sizeElement = $("<span>");
                    $sizeElement.addClass("photos-object-detail-result-variant-size");
                    $sizeElement.text(value.type + '/' + value.size + 'kB (' + value.width + 'x' + value.height + ')');
                    $blockElement.append($sizeElement);

                    let $domainElement = $("<span>");
                    $domainElement.addClass("photos-object-detail-result-variant-domain");
                    $domainElement.text(value.domain);
                    $blockElement.append($domainElement);

                    $("#photos-object-detail-results").append($blockElement);
                });
            }
        );
    });

    // клик по кнопке превью картинки
    $(".photos-object-detail-result-variant-img").live("click", function (event) {
        let $button = $(event.target);
        let url = $button.data("url");
        let link = $button.data("link");
        let title = $button.data("title");

        let $imgElement = $('<img>');
        $imgElement.attr("src", url);
        $imgElement.addClass("photos-object-detail-preview-image");

        let $processButtonImport = $('<button>');
        $processButtonImport.addClass("photos-object-detail-process");
        $processButtonImport.data("url", url);
        $processButtonImport.data("link", link);
        $processButtonImport.val("Загрузить");
        $processButtonImport.text("Загрузить");

        let $previewLinkElement = $("<a>");
        $previewLinkElement.addClass("photos-object-detail-link");
        $previewLinkElement.text(title);
        $previewLinkElement.attr("href", link);
        $previewLinkElement.attr("target", "_blank");

        $("#photos-object-detail-preview")
            .empty()
            .append($previewLinkElement)
            .append($imgElement)
            .append($processButtonImport)
            .show();
    });

    // Загрузка картинки на сервер
    $(".photos-object-detail-process").live("click", function (event) {
        let $button = $(event.target);
        $.post(
            "photos_import.php?act=upload",
            {
                url: $button.data("url"),
                link: $button.data("link"),
                point_id: $("#photos-object-detail-id").val()
            },
            function (response) {
                if (response.photo_id) {
                    $("#photos-object-detail-preview").empty().hide();
                }
            });
    });

    // Клик по кнопке поиска картинок в Яндексе
    $("#photos-object-detail-search").live("click", function () {
        let regionName = $("#photos-object-detail-region").text();
        let objectName = $("#photos-object-detail-title").text();
        let query = objectName + ' ' + regionName;
        let queryUrl = "https://yandex.ru/images/search?text=" + query;
        let win = window.open(queryUrl, '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this website');
        }
    });

    // Очистка блока поиска
    $("#photos-object-clear").live("click", function () {
        $("#photos-object-detail-results").empty();
        $("#photos-object-detail-preview").empty();
        $("#photos-object-detail-region").text("");
        $("#photos-object-detail-title").text("");
        $("#photos-object-detail-id").val("");
        $("#photos-object-detail-latitude").val("");
        $("#photos-object-detail-longitude").val("");
        $("#photos-object-search").hide();
        $("#photos-object-clear").hide();
    });
});
