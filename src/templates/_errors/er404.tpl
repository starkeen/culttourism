<img src="/img/elements/compass.jpg" class="errpage_img_main" />
<p>Запрошенный вами адрес URL <b>https://{$requested}</b> не найден на сервере.</p>
<p>
    Попробуйте начать со стартовой страницы <a href="/">{$host}</a>, посмотрите,
    какие <a href="/city/">регионы</a> у нас представлены
    или воспользуйтесь <a href="/search/">поиском</a>.
</p>
{if !empty($suggestions)}
<div>
    <p>А может вы имели в виду что-то другое?</p>
    <ul>
        {foreach from=$suggestions item=s}
        <li><a href="{$s.url}">{$s.title}</a></li>
        {/foreach}
    </ul>
</div>
{/if}
<p>
    А еще можно попробовать найти что-нибудь <a href="/map/">на карте</a>.
</p>
<div class="m_clear"></div>
<script type="text/javascript">
$(document).ready(function() {
    var yaParams = { error404: { page: url, from: url_referrer } };
    ga('send', 'event', '404', document.location.pathname + document.location.search, document.referrer, { 'nonInteraction': 1 });
    window.yaCounter1209661.reachGoal('error404', { url: document.location.pathname + document.location.search, referer: document.referrer });
});
</script>