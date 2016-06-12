$(document).ready(function () {
    $("#photos-upload-bind-pc").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 200,
        onSelect: function (suggestion) {
            $("#photos-upload-bind-pcid").val(suggestion.data);
        }
    });
    $("#photos-upload-bind-pc-clean").on("click", function () {
        $("#photos-upload-bind-pc").val("");
        $("#photos-upload-bind-pcid").val(0);
    });

    $("#photos-upload-bind-pt").autocomplete({
        serviceUrl: "/search/suggest-object/",
        minChars: 3,
        paramName: "query",
        width: 400,
        transformResult: function (response) {
            var pc = $("#photos-upload-bind-pcid").val();
            var resultSuggestions = [];
            $.map(response.suggestions, function (dataItem) {
                if (pc === "0" || dataItem.city_id.toString() === pc.toString()) {
                    resultSuggestions.push({
                        value: "[" + dataItem.city_title + "] " + dataItem.value,
                        data: dataItem.data
                    });
                }
            });
            return resultSuggestions;
        },
        onSelect: function (suggestion) {
            $("#photos-upload-bind-ptid").val(suggestion.data);
        }
    });
    $("#photos-upload-bind-pt-clean").on("click", function () {
        $("#photos-upload-bind-pt").val("");
        $("#photos-upload-bind-ptid").val(0);
    });

    $("#photos-listfilter-title-clean").on("click", function () {
        $("#photos-listfilter-title").val("");
    });
    $("#photos-listfilter-region-clean").on("click", function () {
        $("#photos-listfilter-region").val("");
        $("#photos-listfilter-regionid").val("0");
    });
    $("#photos-listfilter-object-clean").on("click", function () {
        $("#photos-listfilter-object").val("");
        $("#photos-listfilter-objectid").val("0");
    });

    $("#photos-listfilter-region").autocomplete({
        serviceUrl: "/search/suggest/",
        minChars: 2,
        paramName: "query",
        width: 200,
        onSelect: function (suggestion) {
            $("#photos-listfilter-regionid").val(suggestion.data);
        }
    });

    $("#photos-listfilter-object").autocomplete({
        serviceUrl: "/search/suggest-object/",
        minChars: 3,
        paramName: "query",
        width: 200,
        onSelect: function (suggestion) {
            $("#photos-listfilter-objectid").val(suggestion.data);
        }
    });
});