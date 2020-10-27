$(document).ready(function () {
    // обработка клика при нажатии кнопки "Обработать редирект"
    $(".links-redirect-process").live("click", function (event) {
        if (confirm("Использовать редирект?")) {
            let $element = $(event.target);
            $.post(
                "links.php?act=process-redirect",
                {
                    id: $element.data("id")
                },
                function (response) {
                    if (response.state) {
                        $element.hide();
                    }
                });
        }
    });
    // обработка клика при нажатии кнопки "Удалить ссылку"
    $(".links-delete-process").live("click", function (event) {
        if (confirm("Удалить ссылку?")) {
            let $element = $(event.target);
            let id = $element.data("id");
            let $link = $("#link-id-" + id);
            $.post(
                "links.php?act=process-delete",
                {
                    id: id
                },
                function (response) {
                    if (response.state) {
                        $element.hide();
                        $link.hide();
                    }
                });
        }
    });
    // обработка клика при нажатии кнопки "Редактировать ссылку"
    $(".links-edit-process").live("click", function (event) {
        let $element = $(event.target);
        let id = $element.data("id");
        let $link = $("#link-id-" + id);
        let linkValue = $link.text();
        let newLink = prompt("Новый адрес ссылки", linkValue)
        if (newLink !== null) {
            $.post(
                "links.php?act=process-edit",
                {
                    id: id,
                    value: newLink
                },
                function (response) {
                    if (response.state) {
                        let savedValue = response.value;
                        $link.text(savedValue);
                        $link.attr("href", savedValue);
                    }
                });
        }
    });
    // обработка клика при нажатии кнопки "Удалить объект"
    $(".links-disable-process").live("click", function (event) {
        let $element = $(event.target);
        let id = $element.data("id");
        let $link = $("#link-id-" + id);
        if (confirm("Деактивировать объект?")) {
            $.post(
                "links.php?act=process-disable",
                {
                    id: id
                },
                function (response) {
                    if (response.state) {
                        $element.hide();
                    }
                });
        }
    });
});
