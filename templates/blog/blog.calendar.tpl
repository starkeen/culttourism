<!--
<div id="blog_years">
    {foreach from=$years item=year}
    <a href="/blog/{$year}/">{$year}</a>&nbsp;|
    {/foreach}
</div>
-->

<h2>Календарь {$cur_year}</h2>
{if $entries}
    {foreach from=$entries item=inmonth key=mon}
        <h3 class="blog_calmonth" style="margin-top:20px;">
            {if $mon == 1}
                <a href="/blog/{$cur_year}/0{$mon}/">Январь</a>
            {elseif $mon == 2}
                <a href="/blog/{$cur_year}/0{$mon}/">Февраль</a>
            {elseif $mon == 3}
                <a href="/blog/{$cur_year}/0{$mon}/">Март</a>
            {elseif $mon == 4}
                <a href="/blog/{$cur_year}/0{$mon}/">Апрель</a>
            {elseif $mon == 5}
                <a href="/blog/{$cur_year}/0{$mon}/">Май</a>
            {elseif $mon == 6}
                <a href="/blog/{$cur_year}/0{$mon}/">Июнь</a>
            {elseif $mon == 7}
                <a href="/blog/{$cur_year}/0{$mon}/">Июль</a>
            {elseif $mon == 8}
                <a href="/blog/{$cur_year}/0{$mon}/">Август</a>
            {elseif $mon == 9}
                <a href="/blog/{$cur_year}/0{$mon}/">Сентябрь</a>
            {elseif $mon == 10}
                <a href="/blog/{$cur_year}/{$mon}/">Октябрь</a>
            {elseif $mon == 11}
                <a href="/blog/{$cur_year}/{$mon}/">Ноябрь</a>
            {elseif $mon == 12}
                <a href="/blog/{$cur_year}/{$mon}/">Декабрь</a>
            {/if}
        </h3>
        {foreach from=$inmonth item=entry}
            <div class="blog_record">
                <h4 class="blog_title">
                    <a href="{$entry->getRelativeLink()}">{$entry->br_title}</a>
                </h4>
                <div class="blog_attrs" style="margin-top:1px;">
                    <img src="/img/ico/ico.calendar.gif" class="textmarker"/>&nbsp;{$entry->getHumanDate()}</div>
                {$entry->br_text}
            </div>
        {/foreach}
    {/foreach}
{else}
    <div class="blog_record">
        Извините, записей нет. <a href="/blog/">Вернуться</a>.
    </div>
{/if}
