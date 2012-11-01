(function(w, d, n, s, t) {
    w[n] = w[n] || [];
    w[n].push(function() {
        Ya.Direct.insertInto(94073, "yandex_ad", {
            site_charset: "utf-8",
            ad_format: "direct",
            font_size: 0.9,
            type: "horizontal",
            border_type: "ad",
            limit: 3,
            title_font_size: 2,
            border_radius: true,
            site_bg_color: "FFFFFF",
            header_bg_color: "FEEAC7",
            border_color: "FBE5C0",
            title_color: "0000CC",
            url_color: "006600",
            text_color: "000000",
            hover_color: "0066FF",
            favicon: true
        });
    });
    t = d.documentElement.firstChild;
    s = d.createElement("script");
    s.type = "text/javascript";
    s.src = "http://an.yandex.ru/system/context.js";
    s.setAttribute("async", "true");
    t.insertBefore(s, t.firstChild);
})(window, document, "yandex_context_callbacks");