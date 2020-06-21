(function(w, d, n, s, t) {
    w[n] = w[n] || [];
    w[n].push(function() {
        Ya.Context.AdvManager.render({
            blockId: "R-A-94073-3",
            renderTo: "yandex_ad_city",
            async: true
        });
    });
    t = d.getElementsByTagName("script")[0];
    s = d.createElement("script");
    s.type = "text/javascript";
    s.src = "//an.yandex.ru/system/context.js";
    s.async = true;
    t.parentNode.insertBefore(s, t);
})(this, this.document, "yandexContextAsyncCallbacks");

/** Контекстная реклама Яндекса - общий блок */
(yaads = window.yaads || []).push({
    id: "94073-4",
    render: "#yandex_context_city"
});
