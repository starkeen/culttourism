<img src="/img/elements/server-anavailable-200.png" class="errpage_img_main" />
<p>
    Похоже, у нас проблемы.
    <br/>
    На сервере ведутся ремонтные работы. Сайт будет доступен через несколько минут.
    <br/>
    Приносим свои извинения за доставленные неудобства.
</p>
<script type="text/javascript">
    $(function() {
        ga('send', 'event', '503', document.location.pathname + document.location.search, document.referrer, { 'nonInteraction': 1 });
        window.yaCounter1209661.reachGoal('error-503', { url: document.location.pathname + document.location.search, referer: document.referrer });
    });
</script>

{if $show_trace}
<div style="white-space: pre; border: 1px solid #efefef; position: absolute; left: 500px;">
    {$exception}
</div>
{/if}
