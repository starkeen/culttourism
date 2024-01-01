{if $isAdmin}
<script type="text/javascript" src="/js/editor.js" defer="defer"></script>
{/if}
<div class="blog_record">
    <h2 class="blog_title">
        {$entry->br_title}
        {if $isAdmin}
            <a href="#" class="blog_entry_edit" id="blog_edit_{$entry->getId()}"
               title="редактировать запись"><img src="/img/btn/btn.edit.png"/></a>
            <a href="#" class="blog_entry_delete" id="blog_delete_{$entry->getId()}"
               title="удалить запись"><img src="/img/btn/btn.delete.png"/></a>
        {/if}
    </h2>
    <div class="blog_attrs">
        <img src="/img/ico/ico.calendar.gif" class="textmarker" />&nbsp;
        {$entry->getHumanDate()}
    </div>
    {$entry->br_text}
</div>

<p>
    Перейти:
    <a href="/blog/{$entry->getYear()}/">этот год</a>,
    <a href="/blog/{$entry->getYear()}/{$entry->getMonthString()}/">этот месяц</a>
</p>
