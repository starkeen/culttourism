<form method="get" id="searchrorm_form">
    <a href="http://yandex.ru/"><img src="/img/yandex.png" alt="Поиск от Яндекс" style="vertical-align:middle" /></a>
    <input type="text" id="searchform_input" name="q" value="{$meta.query}" autocomplete="off" />
    <input type="submit" id="searchform_submit" value="Искать" />

    {if $meta.text_source}
        <p>В запросе исправлена опечатка: {$meta.text_result}</p>
    {/if}

    {if $error}
        <p style="color:red;margin:5px;">{$error}</p>
    {/if}
</form>

{if $meta.query && !$error}
<h2>Результаты поиска</h2>

{if $result}

<ul style="margin:1em;">
    {foreach from=$result item=res}
    <li>
        <a href="{$res.url}">{$res.title}</a>
        <br><span style="color:#aaa;font-size: 90%">{$res.descr}</span>
    </li>
    {/foreach}
</ul>

<p>
    Найдено {$meta.resolution}
    <br/>
    {if ($meta.page != 0)}
    <a href="?page={$meta.page - 1}&q={$meta.query}">&#8592; предыдущая</a>
    {/if}
    страница {$meta.page + 1}

    {if ($meta.pages_all > $meta.page + 1)}
    <a href="?page={$meta.page + 1}&q={$meta.query}">следующая &#8594;</a>
    {/if}
</p>
{else}
<p>Увы, по запросу "{$meta.query}" ничего найти не удалось</p>
{/if}

{else}
<p>
    На нашем сайте реализован поиск по названиям населённых пунктов, городов, а также регионов.
    <br/>
    Просто введите в поле выше название населённого пункта или региона, куда планируете поездку и нажмите кнопку "Искать".
    <br/>
    Дополнительные слова ("достопримечательности" и т. п.) вводить не обязательно.
</p>
{/if}
