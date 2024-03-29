<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div id="flickr-import-content">
    <input type="text" id="flickr-import-url" />
    <input type="button" id="flickr-import-button" value="Запрос" />
    <input type="button" id="flickr-import-clean" value="X" />
    <div id="flickr-import-console"></div>
    <div id="flickr-import-preview"></div>

    <div id="flickr-import-add" class="m_hide">
        <input type="text" class="flickr-import-suggest" id="flickr-import-city" value="" placeholder="регион" />
        <input type="hidden" id="flickr-import-photo-id" value="0" />
        <input type="hidden" id="flickr-import-city-id" value="0" />
        <input type="button" id="flickr-import-save-city-clean" value="X" />
        <label>
            <input type="checkbox" id="flickr-import-city-bind" />
            привязать к странице региона
        </label>
        <br />
        <input type="text" class="flickr-import-suggest" id="flickr-import-object" value="" placeholder="объект" />
        <input type="hidden" id="flickr-import-object-id" value="0" />
        <input type="button" id="flickr-import-save-object-clean" value="X" />
        <label>
            <input type="checkbox" id="flickr-import-object-bind" />
            привязать к странице объекта
        </label>

        <br /><br />
        <input type="button" id="flickr-import-save" value="Сохранить" />
    </div>

</div>

<hr />

<div id="flickr-suggestions">
    <ul></ul>
</div>
<div id="flickr-objects-suggestions">
    <ul></ul>
    <input type="button" id="flickr-objects-suggestions-refresh" value="Обновить" />
</div>

<hr />

<div id="flickr-search">
    Перейти к карте для объекта
    <br />
    <input type="text" class="flickr-import-suggest" id="flickr-search-city-suggest" value="" placeholder="регион" />
    <input type="hidden" id="flickr-search-city-id" value="0" />
    <input type="button" id="flickr-search-city-clean" value="X" />
    <br />
    <input type="text" class="flickr-import-suggest" id="flickr-search-points-suggest" value="" placeholder="объект" />
    <input type="hidden" id="flickr-search-points-id" value="0" />
    <input type="button" id="flickr-search-points-clean" value="X" />
    <br />
    <input type="hidden" id="flickr-search-points-latitude" />
    <input type="hidden" id="flickr-search-points-longitude" />
    <input type="button" id="flickr-search-points-go" value="Flickr" disabled="disabled" />
    <input type="button" id="flickr-search-points-go-yandex" value="Яндекс" disabled="disabled" />
</div>


<link rel="stylesheet" href="/css/admin/flickr.css" type="text/css" />
<script type="text/javascript" src="/js/admin/flickr.js" defer="defer"></script>
