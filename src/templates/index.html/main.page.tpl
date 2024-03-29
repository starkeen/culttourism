<h2>Справочник культурно-исторических достопримечательностей</h2>
<div>{$hello_text}</div>
<hr class="clear" />
<p>
    Собранная на нашем сайте интересная информация о более чем {$stat}
    поможет выбрать именно тот туристический маршрут, который вам принесет наибольшее удовлетворение.
</p>
<div class="index-half-left">
    <h3>Записи в блоге</h3>
    {foreach from=$blogentries item=entry}
    <div class="index-blog-record">
        <h4 class="index-blog-title">
            <img src="/img/ico/ico.calendar.gif" alt="Дата" class="textmarker" />
            {$entry.bg_datex}
            <a href="/blog/{$entry.bg_year}/{$entry.bg_month}/{$entry.bg_day}.html">{$entry.br_title}</a>
        </h4>
        {$entry.br_text}
    </div>
    {/foreach}
</div>
<div class="index-half-right">
    <h3>Новости</h3>
    {foreach from=$agrnewsentries item=entry}
    <div class="index-blog-record">
        <h4 class="index-blog-title">
            <img src="/data/favicons/{$entry.ns_host}.png" alt="{$entry.ns_host}" class="textmarker" />
            {$entry.datex}
            <a href="{$entry.ni_url}" target="_blanc">{$entry.ni_title}</a>
        </h4>
        {$entry.ni_text}
        <p class="index-blog-attrs">
            <a href="{$entry.ns_web}">{$entry.ns_title}</a>
        </p>
    </div>
    {/foreach}
</div>

<div id="yandex_ad_index" class="CommonYandexAdverts"></div>
