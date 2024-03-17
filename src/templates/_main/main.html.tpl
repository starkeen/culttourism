<!DOCTYPE html>
<html lang="ru"
      xml:lang="ru"
      xmlns="http://www.w3.org/1999/xhtml"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="https://www.facebook.com/2008/fbml">
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
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@ourways_ru" />
        <meta name="twitter:title" content="{$pageContent->getHead()->getTitle()|escape:"html"}" />
        <meta name="twitter:description" content="{$pageContent->getHead()->getDescription()|escape:"html"}" />
        <meta name="twitter:url" content="{$pageContent->getHead()->getCanonicalUrl()}" />
        <meta name="robots" content="max-image-preview:standard">
        {if $pageContent->getHead()->getRobotsIndexing() !== null}
        <meta name="robots" content="{$pageContent->getHead()->getRobotsIndexing()}"/>
        {/if}
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="revisit-after" content="7 days"/>
        <link rel="stylesheet" href="{$pageContent->getUrlCss()}" type="text/css" media="screen, projection, print"/>
        <link rel="icon" href="/favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
        <link rel="search" href="/search/"/>
        {if $pageContent->getUrlRss() !== null}
        <link rel="alternate" href="{$pageContent->getUrlRss()}" type="application/rss+xml" title="RSS blog"/>
        {/if}
        {if $pageContent->getHead()->getCanonicalUrl()}
            <link rel="canonical" href="{$pageContent->getHead()->getCanonicalUrl()}"/>
        {/if}
        <script type="text/javascript" src="https://www.google.com/recaptcha/api.js" defer="defer"></script>
        <script type="text/javascript"
                src="https://api-maps.yandex.ru/2.1/?apikey={$pageContent->getYandexMapsKey()}&lang=ru_RU&coordorder=longlat"
                defer="defer"></script>
        <script type="text/javascript" src="https://yastatic.net/pcode-native/loaders/loader.js" defer="defer"></script>
        <script type="text/javascript" src="{$pageContent->getUrlJs()}" defer="defer"></script>
        {if !$user->isGuest()}
            <script type="text/javascript" src="/addons/ckeditor/ckeditor.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/ckeditor/adapters/jquery.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/jquery.ui/jquery.ui.core.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/jquery.ui/jquery.ui.datepicker.js" defer="defer"></script>
            <script type="text/javascript" src="/addons/jquery.ui/jquery.ui.datepicker-ru.js" defer="defer"></script>
            <link rel="stylesheet" href="/addons/jquery.ui/ui-lightness/jquery-ui-1.8.2.custom.css" type="text/css"/>
        {/if}
        {if !empty($pageContent->getHead()->getMainMicroDataJSON())}
            <script type="application/ld+json">
                {$pageContent->getHead()->getMainMicroDataJSON()}
            </script>
        {/if}
        {if !empty($pageContent->getHead()->getBreadcrumbsMicroDataJSON())}
            <script type="application/ld+json">
                {$pageContent->getHead()->getBreadcrumbsMicroDataJSON()}
            </script>
        {/if}
        <script type="application/ld+json">
            {$pageContent->getHead()->getWebsiteMicroDataJSON()}
        </script>
        <link rel="preconnect" href="https://mc.yandex.ru" />
        <link rel="preconnect" href="https://www.google.com" />
        <link rel="preconnect" href="https://api-maps.yandex.ru" />
        <link rel="preconnect" href="https://core-renderer-tiles.maps.yandex.net" />
        <link rel="dns-prefetch" href="https://mc.yandex.ru" />
        <link rel="dns-prefetch" href="https://www.google.com" />
        <link rel="dns-prefetch" href="https://api-maps.yandex.ru" />
        <link rel="dns-prefetch" href="https://core-renderer-tiles.maps.yandex.net" />
        <link rel="prerender" href="https://culttourism.ru/list/" />
        <link rel="prerender" href="https://culttourism.ru/map/" />
    </head>
    <body>
        {literal}
            <script async src="https://www.googletagmanager.com/gtag/js?id=G-86NVBND771"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', 'G-86NVBND771');
            </script>
            <script type="text/javascript">(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date(); for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }} k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(1209661, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true }); </script> <noscript><div><img src="https://mc.yandex.ru/watch/1209661" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        {/literal}
        <div id="wrapper">
            <header id="header">
                <div id="header-slogan">Система культурных координат</div>
                <div id="mainmenu-container">
                    <ul id="mainmenu" class="menu_common">
                        <li id="mainmenu_item_0">
                            <a href="/" title="Главная страница">
                                <img src="/img/header/logo-sign-32.png" alt="КТ" width="32" height="32" />
                            </a>
                        </li>
                        <li class="navigate" id="mainmenu_item_1">
                            <a href="/" title="Главная страница">Главная</a>
                        </li>
                        <li class="navigate" id="mainmenu_item_2">
                            <a href="/map/" title="Карта достопримечательностей">Карта</a>
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
                                <img src="/img/elements/lock-32.png" alt="вход" />
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
