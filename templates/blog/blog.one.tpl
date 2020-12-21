<div class="blog_record">
    <h2 class="blog_title">{$entry->br_title}</h2>
    <div class="blog_attrs">
        <img src="/img/ico/ico.calendar.gif" class="textmarker" />&nbsp;
        {$entry->getHumanDate()}
    </div>
    {$entry->br_text}
</div>

<p>
    Перейти:
    <a href="/blog/{$entry->getYear()}/">этот год</a>,
    <a href="/blog/{$entry->getYear()}/{$entry->getMonthNumber()}/">этот месяц</a>
</p>
