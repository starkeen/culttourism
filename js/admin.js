$(document).ready(function() {
    $(".points-moveto").click(function() {
        var oid = $(this).data("oid");
        alert(oid);
        return false;
    });
    $(".points-editprop").click(function() {
        var that = this;
        var oid = $(that).data("oid");
        var prop = $(that).data("prop");
        $.get("points.php", {
            act: "getprop",
            oid: oid,
            prop: prop
        }, function(data) {
            if (data.state) {
                var newval = prompt('Новое значение:', data.out);
                if (newval !== data.out) {
                    $.post("points.php?act=setprop&oid=" + oid + "&prop=" + prop, {
                        value: newval
                    }, function(pdata) {
                        if (pdata.state) {
                            $(that).text(pdata.out);
                        }
                    });
                }
            }
        });
        return false;
    });
});