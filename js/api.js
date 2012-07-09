$(document).ready(function(){
    $(".objects_title").live("click", function() {
        $("#object_card").text("").show().animate({
            width: "80%"
        }, 300);
        $(".preloader").clone().show().appendTo($("#object_card"));
        $("#object_card").load("/api/2/?id="+$(this).attr("id").split("_")[1],
            function() {
                //
            });
        return false;
    });
    $("#do_toggle").live("click", function() {
        $("#object_card").animate({
            width: "0%"
        },500).hide();
    });
});