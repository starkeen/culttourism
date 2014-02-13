$(document).ready(function() {
    $(".points-moveto").click(function() {
        $(".points-moveto").show();
        $(".points-moveselect").remove();
        var that = this;
        var oid = $(that).data("oid");
        var sel = document.createElement("select");
        sel.className = "points-moveselect";
        sel.dataset.oid = oid;
        $.get("points.php", {
            act: "getcity",
            oid: oid
        }, function(data) {
            if (data.state) {
                $.each(data.out, function(i, item) {
                    var option = document.createElement("option");
                    option.text = item.title;
                    option.value = item.id;
                    sel.add(option);
                });
            }
        });
        $(that).hide().parent().append(sel);
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
                if (newval !== data.out && newval !== null) {
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
    $(document).on("change", ".points-moveselect", function() {
        var that = this;
        var oid = $(that).data("oid");
        $.post("points.php?act=setprop&oid=" + oid + "&prop=pt_citypage_id", {
            value: $(that).val()
        }, function(pdata) {
            if (pdata.state) {
                $(that).parent().find(".points-moveto").show();
                $(that).remove();
                $("#points-pctitle-" + oid).css("text-decoration", "line-through");
            }
        });
    });
});