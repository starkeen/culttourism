$(document).ready(function () {
    // обработка клика при нажатии кнопки "Обработать редирект"
    $(".links-redirect-process").live("click", function (event) {
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
    });
});
