<div class="simplewindow">
    <input type="hidden" id="br_id" value="add" />
    <p><b>Добавить запись в блог</b></p>
    <div>
        <input type="text" class="h2 hiddenedit_active" id="eblog_title" value="" />
    </div>
    <textarea class="hiddenedit_active" id="eblog_text"  style="height:300px;"></textarea>
    <hr />
    <div>
        <label for="eblog_active">опубликовать</label>&nbsp;
        <input type="checkbox" id="eblog_active" value="1" />&nbsp;
        <input type="text" id="eblog_date" class="datepicker" value="{$entry.br_day}" readonly="true" />&nbsp;
        в&nbsp;<input type="text" id="eblog_time" style="width:40px;" value="{$entry.br_time}" />
        <br />
        по адресу адрес:
        <span style="background-color:#D3CED3;border:1px solid #A9A9A9;padding-bottom: 1px;">
            https://culttourism.ru/blog/{$entry.bg_year}/{$entry.bg_month}/
            <input type="text" id="eblog_url" style="width:100px;background-color:##DFDCDF;border:none;font-style:italic" value="{$entry.br_url}" />
            .html
        </span>
    </div>
    <hr />
    <div class="formhandler" id="br_save_handler">
        <input type="button" value="сохранить" class="dosave" style="display:inline;" />
        <input type="button" value="отменить" class="doesc" style="display:inline;" />
    </div>
</div>
