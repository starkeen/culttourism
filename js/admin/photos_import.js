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
                    $blockElement.addClass("photos-object-detail-result-variant");

                    let $imgElement = $('<img>');
                    $imgElement.attr("src", value.thumbnailUrl);
                    $imgElement.attr("height", 100);
                    $imgElement.attr("alt", value.title);
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

                    let $buttonImport = $("<button>");
                    $buttonImport.addClass("photos-object-detail-result-variant-import");
                    $buttonImport.data("url", value.url);
                    $buttonImport.data("link", value.context);
                    $buttonImport.attr("title", "Превью");
                    $blockElement.append($buttonImport);

                    let $buttonInfo = $("<button>");
                    $buttonInfo.addClass("photos-object-detail-result-variant-info");
                    $buttonInfo.data("url", value.context);
                    $buttonInfo.attr("title", "Инфо");
                    $blockElement.append($buttonInfo);

                    $("#photos-object-detail-results").append($blockElement);
                });
            }
        );
    });

    // клик по кнопке информации о картинке
    $(".photos-object-detail-result-variant-info").live("click", function (event) {
        let $button = $(event.target);
        let contextUrl = $button.data("url");
        let win = window.open(contextUrl, '_blank');
        if (win) {
            win.focus();
        } else {
            alert('Please allow popups for this website');
        }
    });

    // клик по кнопке импорта картинки
    $(".photos-object-detail-result-variant-import").live("click", function (event) {
        let $button = $(event.target);
        let url = $button.data("url");
        let link = $button.data("link");

        let $imgElement = $('<img>');
        $imgElement.attr("src", url);

        let $buttonImport = $('<button>');
        $buttonImport.addClass("photos-object-detail-result-variant-process");
        $buttonImport.data("url", url);
        $buttonImport.data("link", link);
        $buttonImport.val("Загрузить");
        $buttonImport.text("Загрузить");

        $("#photos-object-detail-preview").empty().append($imgElement).append($buttonImport).show();
    });

    // Загрузка картинки на сервер
    $(".photos-object-detail-result-variant-process").live("click", function (event) {
        let $button = $(event.target);
        $.post(
            "photos_import.php?act=upload",
            {
                url: $button.data("url"),
                link: $button.data("link"),
                point_id: $("#photos-object-detail-id").val()
            },
            function (response) {
                console.log(response);
            });
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
    });
});
