<img class="pageicon" src="/img/admin/ico.a_refs.gif"/>
<h3>{$title}</h3>

<div class="photos-menu-block">
    <a href="./photos.php">Фотографии</a>
    <a href="./flickr.php">Импорт из Flickr</a>
</div>

<div id="photos-page-container">
    <div id="photos-objects-suggestions">
        <ul></ul>
        <input type="button" id="photos-objects-suggestions-refresh" value="Обновить"/>
    </div>

    <div id="photos-object-detail">
        <div id="photos-object-detail-region"></div>
        <div id="photos-object-detail-title"></div>
        <input type="hidden" id="photos-object-detail-id" value="" />
        <input type="hidden" id="photos-object-detail-latitude" value="" />
        <input type="hidden" id="photos-object-detail-longitude" value="" />

        <input type="button" id="photos-object-search" value="Искать">
        <input type="button" id="photos-object-clear" value="Х">
        <div id="photos-object-detail-results"></div>
        <div id="photos-object-detail-preview">
        </div>
    </div>
</div>

<link rel="stylesheet" href="/css/admin/photos_import.css" type="text/css"/>
<script type="text/javascript" src="/js/admin/photos_import.js" defer="defer"></script>
