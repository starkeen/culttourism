<div class="simplewindow">
    <input type="text" id="pt_name_add" class="h2 hiddenedit_active m_larger" />
    <hr />
    <textarea id="pt_description_add" class="hiddenedit_active" style="height:350px;"></textarea>

    <div id="object_additional">
        <p style="text-align: center;margin:2px;">
            <b>Координаты:</b>
            широта <input type="text" id="pt_lat" class="hiddenedit_active" value="" style="width:100px;" />&deg;
            долгота <input type="text" id="pt_lon" class="hiddenedit_active" value="" style="width:100px;" />&deg;
            |
            <input type="checkbox" id="pt_is_best_add" />
            <label for="pt_is_best_add">обязательно к посещению</label>
        </p>
        <fieldset id="point_edit_fieldset">
            <legend>Контактная информация</legend>
            <table style="width: 100%">
                <tr>
                    <td style="width:10px;"><img src="/img/ico/ico.web.png" alt="сайт" class="textmarker" /></td>
                    <td style="width:100px;">веб-сайт:</td>
                    <td><input type="text" id="pt_web_add" class="hiddenedit_active" /></td>
                    <td rowspan="3">&nbsp;</td>
                    <td><img src="/img/ico/ico.house.png" alt="адрес" class="textmarker" />адрес:</td>
                </tr>
                <tr>
                    <td><img src="/img/ico/ico.email.png" alt="e-mail" class="textmarker" /></td>
                    <td nowrap>электронная почта:</td>
                    <td><input type="text" id="pt_email_add" class="hiddenedit_active" /></td>
                    <td rowspan="3"><textarea id="pt_addr_add" class="hiddenedit_active" style="height:95%">{$city_title}</textarea></td>
                </tr>
                <tr>
                    <td><img src="/img/ico/ico.phone.png" alt="телефон" class="textmarker" /></td>
                    <td>телефон:</td>
                    <td><input type="text" id="pt_phone_add" class="hiddenedit_active" /></td>
                </tr>
                <tr>
                    <td><img src="/img/ico/ico.clock.png" alt="часы работы" class="textmarker" /></td>
                    <td nowrap>часы работы:</td>
                    <td><input type="text" id="pt_worktime_add" class="hiddenedit_active" /></td>
                </tr>
            </table>
        </fieldset>
        <div class="formhandler" id="pt_add_handler" style="text-align: right;margin-bottom: 4px;">
            <input type="button" value="сохранить" class="dosave" style="display:inline;" />
            <input type="button" value="отменить" class="doesc" style="display:inline;" />
        </div>
    </div>
</div>