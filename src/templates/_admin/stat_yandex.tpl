<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<table style="width:70%;margin:0 auto;vertical-align: top">
    <tr>
        <td style="vertical-align: top;">
            <p><b>Города</b></p>
            <ul style="text-align:left;">
                <li>всего: {$towns.all}</li>
                <li>в базе: {$towns.base}</li>
                <li>обработано: {$towns.worked}</li>
                <li>осталось: {$towns.remain}</li>
            </ul>
        </td>
        <td style="vertical-align: top;">
            <p><b>Отчёты в кэше</b></p>
            <ol style="text-align:left;">
                {foreach from=$reports item=rep}
                <li>{$rep.ReportID}: {$rep.StatusReport}</li>
                {/foreach}
            </ol>
        </td>
        <td style="vertical-align: top;">
            <p><b>SEO</b></p>
            <ul style="text-align:left;">
                <li>всего городов: {$towns.seo_all}</li>
                <li>обработано: {$towns.seo_worked}</li>
                <li>в топ-10: {$towns.seo_top_10}</li>
                <li>в топ-20: {$towns.seo_top_20}</li>
                <li>в топ-50: {$towns.seo_top_50}</li>
                <li>нет: {$towns.seo_top_none}</li>
            </ul>
        </td>
        <td style="vertical-align: top;">
            <form method="post" action="">
                <input type="submit" name="do_reload_stat" value="Сброс статистики" />
                <br />
                <input type="submit" name="do_stack_empty" value="Сброс очереди отчётов" />
            </form>
            <ul style="text-align:left;">
                <li>позиции от: {$towns.date_positions}</li>
                <li>запросы от: {$towns.date_weights}</li>
            </ul>
            <input type="button" id="ws-token-refresh"
                   data-appid="{$direct_apikey}"
                   value="Обновить токен API ЯД" />
            <ul style="text-align:left;">
                <li>баллы: {$towns.units}</li>
            </ul>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td style="vertical-align: top;">
            <p><b>Рекомендации по добавлению</b></p>

            <table style="background-color:teal; margin:10px;" cellpadding="3" cellspacing="1">
                <tr style="background-color:#DCDCDC;">
                    <th>#</th>
                    <th>Страна</th>
                    <th>Регион</th>
                    <th>Город</th>
                    <th>Запросы</th>
                    <th>&nbsp;</th>
                </tr>
                {foreach from=$stat item=item}
                <tr style="background-color:#fff;">
                    <td>{counter}</td>
                    <td>{$item.country_name}</td>
                    <td>{$item.region_name}</td>
                    <td>
                        <a href="/city/add/?cityname={$item.city_name}">{$item.city_name}</a>
                        <a href="http://wordstat.yandex.ru/#!/?words={$item.city_name} достопримечательности"><img src="/img/new-window.png" /></a>
                    </td>
                    <td align="center">
                        <span title="{$item.ws_weight_min_date}" style="font-style: italic;">{$item.ws_weight_min}</span>
                        /
                        <span title="{$item.ws_weight_date}" style="font-weight:bold;">{$item.ws_weight}</span>
                        /
                        <span title="{$item.ws_weight_max_date}" style="font-style: italic;">{$item.ws_weight_max}</span>
                    </td>
                    <td align="center">
                        <form method="post">
                            <input type="hidden" name="ws_id" value="{$item.ws_id}" />
                            <input type="submit" name="do_delete_town" value="удалить" />
                        </form>
                    </td>
                </tr>
                {/foreach}
            </table>


        </td>
        <td style="vertical-align: top;">
            <p><b>Рекомендации по продвижению</b></p>

            <table style="background-color:teal; margin:10px;" cellpadding="3" cellspacing="1">
                <tr style="background-color:#DCDCDC;">
                    <th>#</th>
                    <th>Страна</th>
                    <th>Регион</th>
                    <th>Город</th>
                    <th>Добавлено</th>
                    <th>Запросы</th>
                    <th>Позиция</th>
                </tr>

                {foreach from=$seo item=item}
                <tr style="background-color:#fff;">
                    <td>{counter name="seo"}</td>
                    <td>{$item.country_name}</td>
                    <td>{$item.region_name}</td>
                    <td>{$item.city_name}</td>
                    <td align="center">{$item.pc_add_date}</td>
                    <td align="center" class="ws-counts" nowrap>
                        <a href="http://wordstat.yandex.ru/#!/?words={$item.city_name} достопримечательности" title="{$item.ws_weight_date}">{$item.ws_weight}</a>
                        <span class="ws-counts-minmax">
                            <sup title="{$item.ws_weight_max_date}">{$item.ws_weight_max}</sup>
                            <sub title="{$item.ws_weight_min_date}">{$item.ws_weight_min}</sub>
                        </span>
                    </td>
                    <td align="center" title="{$item.ws_position_date}">
                        <a href="http://yandex.ru/yandsearch?text={$item.city_name}+достопримечательности&lr=213">{$item.ws_position}</a>
                    </td>
                </tr>
                {/foreach}
            </table>
        </td>
    </tr>
</table>

<script src="/js/admin/stat_yandex.js"></script>
