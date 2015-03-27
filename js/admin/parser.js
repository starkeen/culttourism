$(document).ready(function () {
    $(".parser-start-run").click(function () {
        //запуск загрузки страницы списка
        $.getJSON("parser.php", {
            act: "load_list",
            url: $(".parser-start-url").val()
        }, function (ans) {
            if (ans.state) {
                //
            }
            else {
                alert("Error: " + ans.error.join(";\n"));
            }
        });
    });
});