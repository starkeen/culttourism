$(document).ready(function () {
    $(".parser-start-run").click(function () {
        //запуск загрузки страницы списка
        $(".parser-work-container").addClass("m_hide");
        $.getJSON("parser.php", {
            act: "load_list",
            url: $(".parser-start-url").val()
        }, function (ans) {
            if (ans.state) {
                $(".parser-work-container table").empty().html("<table></table>");
                var tmplt = "<tr>\n\
<td><input type='checkbox' /></td>\n\
<td><a href='{{link}}'>{{name}}</a></td>\n\
<td><span></span></td>\n\
</tr>";
                $(".parser-work-container table").html(
                        $.map(ans.data, function (item) {
                            return tmplt.replace(/{{name}}/, item.title)
                                    .replace(/{{link}}/, item.link);
                        })
                        .join(""));
                $(".parser-work-container").removeClass("m_hide");
            }
            else {
                var message = ans.error.join(";\n");
                if (message === '') {
                    message = 'ошибка разбора списка';
                }
                alert("Error: " + message);
            }
        });
    });
    $(".parser-work-import").click(function () {
        //запуск разбора выбранных единиц страницы
        $(".parser-work-container table input").each(function () {
            var $that = $(this);
            if ($that.attr("checked")) {
                var pointUrl = $that.parents('tr').find("a").attr("href");
                $.getJSON("parser.php", {
                    act: "load_item",
                    mode: "auto",
                    city: $(".parser-work-region").val(),
                    pcid: $(".parser-work-region-id").val(),
                    url: pointUrl
                }, function (answer) {
                    if (answer.state) {
                        $that.parents('tr').find("span")
                                .text('ok:' + $.map(answer.data, function (item, i) {
                                    if (item !== '') {
                                        return i;
                                    }
                                }).join(","));
                        $that.attr("disabled", "disabled");
                    }
                    else {
                        var message = answer.error.join(";\n");
                        if (message === '') {
                            message = 'неизвестная ошибка разбора точки ' + pointUrl;
                        }
                        alert("Error: " + message);
                    }
                });
            }
        });
    });
    $(".parser-work-all").click(function () {
        if ($(".parser-work-container table input:checked").length === 0) {
            $(".parser-work-container table input").each(function () {
                $(this).attr("checked", "checked");
            });
        } else {
            $(".parser-work-container table input").each(function () {
                $(this).removeAttr("checked");
            });
        }
        return false;
    });
    $(".parser-work-region").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 400,
        onSelect: function (suggestion) {
            $('.parser-work-region-id').val(suggestion.data);
        }
    });
});