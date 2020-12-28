{literal}
<script type="text/javascript">
    $(document).ready(function () {
        $('#addmdbtn').click(function () {
            window.location = 'modules.php?id=add';
        });
    });
</script>
{/literal}
<h3>Разделы и страницы сайта</h3>
<table width="100%">
    <tr>
        <td width="300px;" valign="top">
            <p style="color: #1E90FF;"><strong>Зарегистрированные модули</strong></p>
            <ul id="modlist" style="margin-left:0;">
                {foreach from=$mod_list item=module key=module_id}
                <li><a href="?id={$module_id}">
                        {if $module.md_active == 0}<s>{/if}{$module.md_name}{if $module.md_active == 0}</s>{/if}</a>
                    {if $module.md_redirect !== null}<span style="color:red;"><b>R</b></span>{/if}
                    {if $module.md_tree}
                    <ul style="margin-left:0px;">
                        {foreach from=$module.md_tree item=mchld key=mcld_id}
                        <li><a href="?id={$mcld_id}">{if $mchld.md_active == 0}<s>{/if}{$mchld.md_name}{if $mchld.md_active == 0}</s>{/if}</a>
                            {if $mchld.md_redirect !== null}<span style="color:red;"><b>R</b></span>{/if}
                        </li>
                        {/foreach}
                    </ul>
                    {/if}
                </li>
                {/foreach}
            </ul>
            <hr>
            <div style="text-align:center;">
                <button id="addmdbtn" value="Добавить раздел">Добавить раздел</button>
            </div>
            <div>
                <p><b>Внимание!</b></p>
                <p>при загрузке картинок будьте внимательны, есть ряд требований и ограничений:</p>
                <ul>
                    <li>Размер изображения по ширине не должен превышать 540 пикс. Всё, что шире - будет обрезано справа.</li>
                    <li>Имя файла не должно содержать русских букв. В противном случае многие пользователи его просто не увидят.</li>
                </ul>
            </div>
        </td>
        <td valign="top">
            {if $mod_id}
            <p style="color: #1E90FF;"><strong>{$mod_item.md_name}</strong></p>
            <form method="POST" style="background-color: #F0FFFF; padding: 5px; border: 1px solid #87CEEB;">
                <input type="hidden" name="actiontype" value="{$mod_id}">
                <table width="100%">
                    <tr>
                        <td nowrap align="right"><label for="imd_name">Название модуля</label></td>
                        <td width="90%"><input title="Название модуля" type="text" id="imd_name" name="md_name" value="{$mod_item.md_name}" style="width:100%;" {if $mod_id!='add'}readonly="true" disabled="true"{/if} /></td>
                        <td nowrap align="right"><label for="imd_pid">Внутри модуля</label></td>
                        <td>
                            <select name="md_pid" id="imd_pid" {if $mod_id!='add'}disabled{/if} title="Если страница внутри модуля">
                                    <option value="null">Нет</option>
                                {foreach from=$mod_list item=mod key=mod_id}
                                <option value="{$mod_id}" {if $mod_item.md_pid == $mod_id}selected{/if}>{$mod.md_name}</option>
                                {/foreach}
                            </select>

                            {if $is_admin}
                            <input title="Сортировка" type="text" id="imd_sort" name="md_sort" value="{$mod_item.md_sort}" style="width:3em;" />
                            {else}
                            <input type="hidden" name="md_sort" value="{$mod_item.md_sort}">
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td nowrap align="right"><label for="imd_title">Заголовок страницы</label></td>
                        <td width="90%" colspan="3"><input type="text" id="imd_title" name="md_title" value="{$mod_item.md_title}" style="width:100%;" title="Это будет видно в заголовке браузера" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="imd_url">URL</label></td>
                        <td nowrap><span id="suburl">{$site_url}{if $mod_item.md_pid!=0}{$mod_item.parent.md_url}/&nbsp;{/if}</span><input type="text" id="imd_url" name="md_url" value="{$mod_item.md_url}" style="width:50%;" /></td>
                        {if $is_admin}
                        <td align="right" nowrap><label for="imd_redirect">URL-редирект</label></td>
                        <td nowrap>
                            <input type="text" id="imd_redirect" name="md_redirect" value="{$mod_item.md_redirect}" />
                            <input type="hidden" name="md_redirect_flg" value="0">
                            <input type="checkbox" id="imd_redirect_flg" name="md_redirect_flg" value="1" {if $mod_item.md_redirect !== null}checked{/if}>
                                   <label for="imd_redirect_flg">- включен</label>
                        </td>
                        {else}
                        <td align="right" nowrap><label for="imd_redirect">URL-редирект</label></td>
                        <td nowrap>
                            <input type="hidden" name="md_redirect" value="{$mod_item.md_redirect}" />
                            <input type="text" id="imd_redirect" name="md_redirect" value="{$mod_item.md_redirect}" disabled="true" />
                            {if $mod_item.md_redirect !== null}
                            <input type="hidden" name="md_redirect_flg" value="0">
                            {else}
                            <input type="hidden" name="md_redirect_flg" value="1">
                            {/if}
                        </td>
                        {/if}
                    </tr>
                    <tr>
                        <td nowrap align="right"><label for="imd_keywords">Ключевые слова</label></td>
                        <td width="90%" colspan="3"><input type="text" id="imd_keywords" name="md_keywords" value="{if !$mod_item.md_keywords}Перечислите через запятую{else}{$mod_item.md_keywords}{/if}" style="width:100%;" /></td>
                    </tr>
                    <tr>
                        <td nowrap align="right" valign="top"><label for="imd_description">Описание раздела</label></td>
                        <td width="90%" colspan="3">
                            <textarea id="imd_description" name="md_description" style="width:100%;">{$mod_item.md_description}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            {if $text_edit}
                            <textarea id="imd_pagecontent" name="md_pagecontent" style="width:100%;height:400px" cols="80" rows="10">{$mod_item.md_pagecontent}</textarea>
                            {else}
                            <input type="hidden" name="md_pagecontent" value="" />
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td><input type="submit" name="sender" value="Сохранить" /></td>
                        <td colspan="2" nowrap>
                            <input type="hidden" name="md_active" value="0">
                            <input type="checkbox" id="imd_active" name="md_active" value="1" {if $mod_item.md_active}checked{/if} title="Включение и отключение страниц раздела">
                                   <label for="imd_active" title="Включение и отключение страниц раздела">- раздел активен</label>

                            <input type="hidden" name="md_counters" value="0">
                            <input type="checkbox" id="imd_counters" name="md_counters" value="1" {if $mod_item.md_counters}checked{/if} title="Отображение блока счетчиков на странице">
                                   <label for="imd_counters" title="Отображение блока счетчиков на странице">- показывать счетчики</label>

                            <input type="hidden" name="md_css" value="0">
                            <input type="checkbox" id="imd_css" name="md_css" value="1" {if $mod_item.md_css}checked{/if} title="Использовать отдельные таблицы стилей">
                                   <label for="imd_css" title="Использовать отдельные таблицы стилей">- таблицы стилей</label>


                        </td>
                        <td>
                            <label for="imd_robots">индексация</label>
                            <select id="imd_robots" name="md_robots" title="Показывать или нет страницу поисковым роботам">
                                <option value="index, follow" {if $mod_item.md_robots=='index, follow'}selected="true"{/if}>index, follow</option>
                                <option value="noindex, nofollow" {if $mod_item.md_robots=='noindex, nofollow'}selected="true"{/if}>noindex, nofollow</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>

            {else}

            <p>Выберите страничку из списка слева</p>

            {/if}
        </td>
    </tr>
</table>

<script>
    $(document).ready(function () {
        $('#imd_pagecontent').ckeditor(function () {}, {
            customConfig: "/config/config.cke4.js",
            height: '400px',
            toolbar: "Lite"
        });
    });
</script>
