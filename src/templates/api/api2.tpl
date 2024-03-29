<div id="object_card_text">
    <h2>{$object.pt_name}</h2>
    <div class="object_fulltext">
        {$object.pt_description}
        {if $object.pt_adress || $object.pt_worktime || $object.pt_website || $object.pt_email || $object.pt_phone}
        <fieldset>
            <legend>Контактная информация</legend>
            <ul id="object_contacts">
                {if $object.pt_adress}
                <li><img src="/img/ico/ico.house.png" alt="адрес" title="Адрес" class="textmarker" />{$object.pt_adress}</li>
                {/if}
                {if $object.pt_phone}
                <li><img src="/img/ico/ico.phone.png" alt="телефон" title="Телефон" class="textmarker" />{$object.pt_phone}</li>
                {/if}
                {if $object.pt_worktime}
                <li><img src="/img/ico/ico.clock.png" alt="часы работы" title="Часы работы" class="textmarker" />{$object.pt_worktime}</li>
                {/if}
                {if $object.pt_website}
                <li><img src="/img/ico/ico.web.png" alt="сайт" title="Сайт" class="textmarker" /><a href="{$object.pt_website}">{$object.pt_website}</a></li>
                {/if}
                {if $object.pt_email}
                <li><img src="/img/ico/ico.email.png" alt="e-mail" title="Электронная почта" class="textmarker" /><a href="mailto:{$object.pt_email}">{$object.pt_email}</a></li>
                {/if}
            </ul>
        </fieldset>
        {/if}
    </div>
</div>