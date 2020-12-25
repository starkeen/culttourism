<div class="list-half-left">
    {$list.ls_text}
</div>

<ul class="list-half-right">
    {foreach from=$list_items item=pt}
    <li>{$pt.pc_title}: <a href="{$pt.url_canonical}" class="objlink">{$pt.pt_name}</a></li>
    {/foreach}
</ul>

<div style="clear:both;"></div>

<h2>{$list.ls_title} на карте</h2>
<div id="list-map" class="list-map"></div>
<input type="hidden" id="list-id" value="{$list.ls_id}" />
