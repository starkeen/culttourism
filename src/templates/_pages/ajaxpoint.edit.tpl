<div class="simplewindow">
    <input type="hidden" id="pt_id" value="{$object.pt_id}" />
    <h2 id="pt_name_hidd" class="hiddenedit">{$object.pt_name}</h2>
    <input type="text" id="pt_name_edit" class="h2 m_larger" style="display:none;width: 100%" />
    <div class="formhandler" id="pt_name_handler">
        <input type="button" value="сохранить" class="dosave" />
        <input type="button" value="отменить" class="doesc" />
    </div>
    <hr />
    <div id="pt_description_hidd" class="hiddenedit">{$object.pt_description}</div>
    <textarea id="pt_description_edit" style="display:none;"></textarea>
    <div class="formhandler" id="pt_description_handler">
        <input type="button" value="сохранить" class="dosave" />
        <input type="button" value="отменить" class="doesc" />
    </div>

    <div id="object_additional">
        <input type="checkbox" id="pt_is_best_edit" {if $object.pt_is_best}checked{/if} />
               <label for="pt_is_best_edit">обязательно к посещению</label>
        <fieldset id="point_edit_fieldset">
            <legend>Контактная информация</legend>
            <img id="do_cont_edit" src="/img/ico/ico.edit.png" />
            <div>
                <img src="/img/ico/ico.house.png" alt="Адрес" title="Адрес" class="textmarker" />
                <span id="pt_cont_adress" class="edit_cont">{$object.pt_adress}</span>
                <input type="text" id="pt_cont_adress_edit" class="hiddenedit_cont" />
            </div>
            <div>
                <img src="/img/ico/ico.phone.png" alt="Телефон" title="Телефон" class="textmarker" />
                <span id="pt_cont_phone" class="edit_cont">{$object.pt_phone}</span>
                <input type="text" id="pt_cont_phone_edit" class="hiddenedit_cont" />
            </div>
            <div>
                <img src="/img/ico/ico.clock.png" alt="Время работы" title="Часы работы" class="textmarker" />
                <span id="pt_cont_worktime" class="edit_cont">{$object.pt_worktime}</span>
                <input type="text" id="pt_cont_worktime_edit" class="hiddenedit_cont" />
            </div>
            <div>
                <img src="/img/ico/ico.web.png" alt="Сайт" title="Сайт" class="textmarker" />
                <a id="pt_cont_website" class="edit_cont" href="{$object.pt_website}">{$object.pt_website}</a>
                <input type="text" id="pt_cont_website_edit" class="hiddenedit_cont" />
            </div>
            <div class="formhandler" id="pt_contacts_handler" style="text-align:right">
                <input type="button" value="сохранить" class="dosave" />
                <input type="button" value="отменить" class="doesc" />
            </div>
        </fieldset>
    </div>
</div>