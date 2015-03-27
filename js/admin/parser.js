$(document).ready(function () {
    $(".parser-start-run").click(function () {
        //запуск загрузки страницы списка
        $.getJSON("parser.php", {
            act: "load_list",
            url: $(".parser-start-url").val()
        }, function (ans) {
            if (ans.state) {
                $(".parser-work-container").empty().html("<ul></ul>");
                var tmplt = "<li><a href='#'>{{name}}</a></li>";
                $(".parser-work-container ul").html($.map(ans.data, function (item) {
                    return tmplt.replace(/{{name}}/, item.title);
                }).join(""));
            }
            else {
                alert("Error: " + ans.error.join(";\n"));
            }
        });
    });
});