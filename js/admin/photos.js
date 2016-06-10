$(document).ready(function () {
    $("#photos-upload-bind-pc").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 400,
        onSelect: function (suggestion) {
            $("#photos-upload-bind-pcid").val(suggestion.data);
        }
    });
    $("#photos-upload-bind-pc-clean").on("click", function () {
        $("#photos-upload-bind-pc").text("");
        $("#photos-upload-bind-pcid").val(0);
    });
});