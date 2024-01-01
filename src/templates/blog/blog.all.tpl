{if $isAdmin}
    <script type="text/javascript" src="/js/editor.js" defer="defer"></script>
    <div style="text-align: right;float:right;">
        <a href="#" id="blog_entry_add"><b>добавить запись</b></a>
    </div>
{/if}

{if $entries}
    {foreach from=$entries item=entry}
        {if $entry->isShown() || $isAdmin}
            <div class="blog_record">
                <h2 class="blog_title">
                    <a href="{$entry->getRelativeLink()}">{$entry->br_title}</a>

                    {if $isAdmin}
                        <a href="#" class="blog_entry_edit" id="blog_edit_{$entry->getId()}"
                           title="редактировать запись"><img src="/img/btn/btn.edit.png"/></a>
                        <a href="#" class="blog_entry_delete" id="blog_delete_{$entry->getId()}"
                           title="удалить запись"><img src="/img/btn/btn.delete.png"/></a>
                    {/if}
                </h2>
                <div class="blog_attrs">
                    <img src="/img/ico/ico.calendar.gif" class="textmarker"/>
                    {$entry->getHumanDate()}
                    {if !$entry->isActive() && $isAdmin}, отключено{/if}
                    {if !$entry->isShown() && $isAdmin}, отложено{/if}
                </div>
                {$entry->getText()}
            </div>
        {/if}
    {/foreach}

{else}

    <div class="blog_record">
        Извините, записей нет. <a href="/blog/">Вернуться</a>.
    </div>

{/if}
