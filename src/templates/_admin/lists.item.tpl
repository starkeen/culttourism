<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<p>Список "{$list.ls_title}"</p>

<table style="width:100%;">
    <tr>
        <td valign="top" style="max-width: 50%;">

            <b>Данные списка</b>
            <form method="post">
                <table style="width:100%;">
                    <tr>
                        <td>Наименование</td>
                        <td><input type="text" name="ls_title" value="{$list.ls_title}" style="width:99%;" /></td>
                    </tr>
                    <tr>
                        <td>URL-часть</td>
                        <td><input type="text" name="ls_slugline" value="{$list.ls_slugline}" style="width:99%;" /></td>
                    </tr>
                    <tr>
                        <td>Ключевые слова</td>
                        <td><input type="text" name="ls_keywords" value="{$list.ls_keywords}" style="width:99%;" /></td>
                    </tr>
                    <tr>
                        <td colspan="2">Мета-описание</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <textarea name="ls_description" id="ls_description" style="width:99%;">{$list.ls_description}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Текст</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <textarea name="ls_text" id="ls_text" style="width:99%;">{$list.ls_text}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            Порядок: <input type="text" name="ls_order" value="{$list.ls_order}" style="width:50px;" />
                            Активность:
                            <input type="hidden" name="ls_active" value="0" />
                            <input type="checkbox" name="ls_active" value="1" {if $list.ls_active == 1}checked{/if} />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" value="Сохранить" />
                        </td>
                    </tr>
                </table>
            </form>

        </td>
        <td valign="top" style="max-width: 50%;">

            <b>Список входящих объектов</b>
            <form method="post" action="?id={$list.ls_id}&act=add">
                <table class="commontable" cellspacing="1">
                    <tr>
                        <th colspan="2">№</th>
                        <th>ID</th>
                        <th>Регион</th>
                        <th>Название</th>
                        <th>Д</th>
                        <th>Вес</th>
                        <th>Порядок</th>
                        <th>Активность</th>
                    </tr>
                    {foreach from=$list_items item=li}
                    <tr>
                        <td style="text-align: center;">{counter}</td>
                        <td style="text-align: center;"><img src="/img/points/x16/{$li.tp_icon}" alt="{$li.tp_short}" title="{$li.tp_short}" /></td>
                        <td style="text-align: center;">{$li.pt_id}</td>
                        <td><a href="{$li.url_region}" target="_blank">{$li.pc_title}</a></td>
                        <td><a href="{$li.url_canonical}" target="_blank">{$li.pt_name}</a></td>
                        <td style="text-align: center; background-color: {if $li.len_descr < 100}#FF4500{elseif $li.len_descr < 200}#FFA583{elseif $li.len_descr < 500}#FFA583{else}#CCE4CC{/if};">{$li.len_descr}</td>
                        <td style="text-align: center;">{$li.pt_rank}</td>
                        <td style="text-align: center;"><a href="#" class="list-edit-attr" data-field="li_order" data-ptid="{$li.pt_id}">{$li.li_order}</a></td>
                        <td style="text-align: center;"><a href="#" class="list-edit-attr" data-field="li_active" data-ptid="{$li.pt_id}">{if $li.li_active}<b>ВКЛ</b>{else}откл{/if}</a></td>
                    </tr>
                    {/foreach}
                    <tr>
                        <td colspan="2" style="text-align: center;"><img src="/img/btn/btn.add.png" /></td>
                        <td><input type="text" name="add_id" id="list-add-id" value="" style="width:4em" /></td>
                        <td colspan="4"><input type="text" id="list-add-name" value="" style="width:98%" autocomplete="off" /></td>
                        <td></td>
                        <td><input type="submit" value="добавить" /></td>
                    </tr>
                </table>
            </form>

        </td>
    </tr>
</table>


<script>
    $(document).ready(function () {
        $('#list-add-name').autocomplete({
            serviceUrl: "lists.php?suggest&lid={$list.ls_id}",
            minChars: 4,
            paramName: "query",
            onSelect: function (suggestion) {
                $('#list-add-id').val(suggestion.oid);
            }
        });
        $('#ls_text').ckeditor(function () {
        }, {
            customConfig: "/config/config.cke4.js",
            height: '370px',
            toolbar: "Lite"
        });
        $(".list-edit-attr").click(function () {
            var that = this;
            if ($(that).data("field") === "li_active") {
                var newval = ($(that).text() === "ВКЛ") ? 0 : 1;
            } else {
                var newval = prompt("Новое значение", $(this).text());
            }
            $.getJSON("lists.php?json&act=editfield", {
                lid: {$list.ls_id},
                ptid: $(that).data("ptid"),
                field: $(that).data("field"),
                val: newval
            }, function (data) {
                if (data.state) {
                    if ($(that).data("field") === "li_active" && data.newval == 1) {
                        data.newval = "ВКЛ"
                    }
                    if ($(that).data("field") === "li_active" && data.newval == 0) {
                        data.newval = "откл"
                    }
                    $(that).text(data.newval);
                }
            });
            return false;
        });
    });
</script>
<style>
    .list-edit-attr {
        text-decoration: none;
        color: #0e506e;
        border-bottom:1px dotted #0e506e;
    }
</style>