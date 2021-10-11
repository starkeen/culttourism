{$index_text}
<h2>Списки достопримечательностей</h2>

<ul class="list-index-container">
    {foreach from=$index_lists item=list}
    <li class="list-index-item">
        <div class="list-index-item-background" style="background-image: url({$list.image});"></div>
        <a class="list-index-item-title" href="/list/{$list.ls_slugline}.html">
            {$list.ls_title}
        </a>
        <p class="list-index-item-description">
            {$list.ls_description}
        </p>
    </li>
    {/foreach}
</ul>
