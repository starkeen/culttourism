<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:og="http://ogp.me/ns#" xmlns:fb="https://www.facebook.com/2008/fbml">
    <head>
        <meta charset="utf-8"/>
        <!--[if lt IE 9]>
        <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <title>{$pageContent->getHead()->getTitle()}</title>
        <meta name="keywords" content="{$pageContent->getHead()->getKeywords()|escape:"html"}"/>
        <meta name="description" content="{$pageContent->getHead()->getDescription()|escape:"html"}"/>
        <meta property="fb:pages" content="251308854921643" />
        <meta property="fb:admins" content="787133747" />
        <meta property="fb:app_id" content="345000545624253"/>
        {foreach from=$pageContent->getHead()->getCustomMetas() key=property item=content}
            <meta property="{$property}" content="{$content|truncate:800:"…"}"/>
        {/foreach}
        {if $pageContent->getHead()->getRobotsIndexing() !== null}
        <meta name="robots" content="{$pageContent->getHead()->getRobotsIndexing()}"/>
        {/if}
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="revisit-after" content="7 days"/>
        <link rel="stylesheet" href="/css/{$pageContent->getUrlCss()}" type="text/css" media="screen, projection, print"/>
        <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
        <link rel="search" href="/search/"/>
        <link rel="alternate" href="{$pageContent->getUrlRss()}" type="application/rss+xml" title="RSS blog"/>
        {if $pageContent->getHead()->getCanonicalUrl()}
            <link rel="canonical" href="{$pageContent->getHead()->getCanonicalUrl()}"/>
        {/if}
        <script type="text/javascript" src="https://www.google.com/recaptcha/api.js" defer="defer"></script>
        <script type="text/javascript"
                src="https://api-maps.yandex.ru/2.1/?apikey=74d288bb-04a5-43b1-bf52-e90eeccd2683&lang=ru_RU&coordorder=longlat"
                defer="defer"></script>
        <script type="text/javascript" src="https://yastatic.net/pcode-native/loaders/loader.js" defer="defer"></script>
        <script type="text/javascript" src="/js/{$pageContent->getUrlJs()}" defer="defer"></script>
        {if !$user->isGuest()}
            <script type="text/javascript" src="/addons/ckeditor/ckeditor.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/ckeditor/adapters/jquery.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/jquery.ui/jquery.ui.core.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/jquery.ui/jquery.ui.datepicker.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/jquery.ui/jquery.ui.datepicker-ru.js" defer="defer"></script>
            <link rel="stylesheet" href="/addons/jquery.ui/ui-lightness/jquery-ui-1.8.2.custom.css" type="text/css"/>
        {/if}
        {if !empty($pageContent->getHead()->getMicroDataJSON())}
            <script type="application/ld+json">
                {$pageContent->getHead()->getMicroDataJSON()}
            </script>
        {/if}
    </head>
    <body>
        {literal}
        <script type="text/javascript">
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
            ga('create', 'UA-6799673-8', 'auto');
            ga('send', 'pageview');
            window.onerror = function (msg, url, line) {
                var preventErrorAlert = true;
                ga('send', 'event', 'JS Error', msg, navigator.userAgent + ' -> ' + url + " : " + line, 0);
                return preventErrorAlert;
            };
        </script>
        <script type="text/javascript">(function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter1209661 = new Ya.Metrika({ id:1209661, clickmap:true, trackLinks:true, accurateTrackBounce:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/watch.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks"); </script> <noscript><div><img src="https://mc.yandex.ru/watch/1209661" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        {/literal}
        <div id="wrapper">
            <header id="header">
                <div id="header-slogan">Система культурных координат</div>
                <div id="mainmenu-container">
                    <ul id="mainmenu" class="menu_common">
                        <li id="mainmenu_item_0">
                            <a href="/" title="Главная страница">
                                <img src="/img/header/logo-sign-32.png" />
                            </a>
                        </li>
                        <li class="navigate" id="mainmenu_item_1">
                            <a href="/" title="Главная страница">Главная</a>
                        </li>
                        <li class="navigate" id="mainmenu_item_2">
                            <a href="/city/" title="Города">Города</a>
                        </li>
                        <li class="navigate" id="mainmenu_item_3">
                            <a href="/blog/" title="Блог проекта">Блог</a>
                        </li>
                        <li class="navigate" id="mainmenu_item_4">
                            <a href="/feedback/newpoint/" title="Добавить объект">Добавить</a>
                        </li>
                        <li class="navigate" id="mainmenu_item_5">
                            <a href="/about/" title="О проекте">О проекте</a>
                        </li>
                        <li id="mainmenu_item_6">
                            <form method="get" action="/search/">
                                <input type="text" id="search_mainform_q" name="q" value="" placeholder="Поиск" />
                            </form>
                        </li>
                        <li id="mainmenu_item_7">
                            <a id="show_auth_form" href="/sign/in/" title="Вход" rel="nofollow">
                                <img src="/img/elements/lock-32.png" />
                            </a>
                        </li>
                    </ul>
                </div>
            </header><!-- #header-->
            <div id="content">
                {if $pageContent->getH1()}<h1>{$pageContent->getH1()}</h1>{/if}
                {$pageContent->getBody()}
                <div class="content-scroll-buttons m_hide">
                    <img src="/img/btn/btn.scroll-top.48.png" alt="go top" />
                </div>
            </div><!-- #content-->
        </div><!-- #wrapper -->
        <footer id="footer">
            <div id="footer_conters"></div>
        </footer><!-- #footer -->
        <!-- Запрос на восстановление регистрационной информации -->
    </body>
</html>
