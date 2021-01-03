<h3>Заполните, пожалуйста, все поля формы</h3>
<p>
    Здесь вы можете выступить с предложением, вопросом, жалобой.
    Добавить объект в нашу базу можно в <a href="/feedback/newpoint/">соответствующем разделе</a>.
</p>
<form method="post" id="feedform">
    <input type="text" name="ftextcheck" id="ftextcheck" value="" />
    <table class="feedtable" style="margin:10px auto;">
        <tr>
            <td style="width:20%"><label for="fname">Ваше имя</label></td>
            <td colspan="3">
                <input type="text" id="fname" name="fname"
                       class="feedinput"
                       value="{$fname}"
                       placeholder="Иван Иванов"
                       autocomplete="off"
                       style="width:98%" />
                {if $error=='fname'}
                <span class="feedhighlight">Вы не ввели имя</span>
                {/if}
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <label for="ftext">Текст сообщения</label>
                {if $error=='ftext'}
                <span class="feedhighlight">Вы не ввели текст сообщения</span>
                {/if}
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <textarea name="ftext" id="ftext"
                          class="feedinput"
                          style="width:98%;margin:0 10px;">{$ftext}</textarea>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                Если вы хотите, чтобы мы связались с вами,
                оставьте свои контактные данные.
            </td>
        </tr>
        <tr>
            <td><label for="fmail">Электронная&nbsp;почта</label></td>
            <td colspan="3">
                <input type="email" id="fmail" name="fmail"
                       autocomplete="off"
                       class="feedinput"
                       value="{$fmail}"
                       placeholder="ivan@ivanov.ru"
                       style="width:50%" />
            </td>
        </tr>
        <tr>
            <td colspan="4"><hr/></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:center; padding: 5px">
                <input type="text" name="fsurname" id="feedback-form-surname" value="" />

                <input type="submit"
                       class="bigbutton g-recaptcha"
                       data-sitekey="{$recaptcha_key}"
                       data-callback="reCaptchaCallback"
                       value="Отправить"/>
            </td>
        </tr>
    </table>
</form>

<p>
    Нажимая кнопку &laquo;Отправить&raquo;, вы соглашаетесь со всеми пунктами
    <a href="/about/">&laquo;Пользовательского соглашения&raquo;</a>.
</p>

<style>
    #feedback-form-surname {
        display: none;
    }
</style>
