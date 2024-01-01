<div class="simplewindow">
    <input type="hidden" id="br_id" value="{$entry->br_id}" />
    <p><b>Редактировать запись в блоге</b></p>
    <div>
        <input type="text" class="h2 hiddenedit_active" id="eblog_title" value="{$entry->br_title}" />
    </div>
    <textarea class="hiddenedit_active" id="eblog_text"  style="height:300px;">{$entry->br_text}</textarea>
    <hr />
    <div>
        <label for="eblog_active">опубликовать</label>&nbsp;
        <input type="checkbox" id="eblog_active" value="1" {if $entry->br_active==1}checked{/if} />&nbsp;
               <input type="text" id="eblog_date" class="datepicker" value="{$entry->getHumanDate()}" readonly="true" />&nbsp;
        в&nbsp;<input type="text" id="eblog_time" style="width:40px;" value="{$entry->getTime()}" />
        <br />
        по адресу адрес:
        <span style="background-color:#D3CED3;border:1px solid #A9A9A9;padding-bottom: 1px;">
            https://culttourism.ru/blog/{$entry->getYear()}/{$entry->getMonthNumber()}/
            <input type="text" id="eblog_url" style="width:100px;background-color:##DFDCDF;border:none;font-style:italic" value="{$entry->br_url}" />
            .html
        </span>
    </div>
    <hr />
    <div class="formhandler" id="br_save_handler">
        <input type="button" value="сохранить" class="dosave" style="display:inline;" />
        <input type="button" value="отменить" class="doesc" style="display:inline;" />
    </div>
</div>
