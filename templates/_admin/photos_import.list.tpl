<img class="pageicon" src="/img/admin/ico.a_refs.gif"/>
<h3>{$title}</h3>

<div class="photos-menu-block">
    <a href="./photos.php">Фотографии</a>
</div>

<div id="photos-page-container">
    <div id="photos-objects-suggestions">
        <ul></ul>
        <input type="button" id="photos-objects-suggestions-refresh" value="Обновить"/>
    </div>

    <div id="photos-object-detail">
        <div id="photos-object-detail-region"></div>
        <div id="photos-object-detail-title"></div>
        <img id="photos-object-detail-search"
             src="/img/btn/btn.search.png"
             title="Искать" />
        <div id="photos-object-detail-address"></div>
        <input type="hidden" id="photos-object-detail-id" value="" />
        <input type="hidden" id="photos-object-detail-latitude" value="" />
        <input type="hidden" id="photos-object-detail-longitude" value="" />

        <input type="button" id="photos-object-search" value="Искать">
        <input type="button" id="photos-object-clear" value="Х">
        <div id="photos-object-detail-results"></div>
        <div id="photos-object-detail-preview">
        </div>
        <div id="photos-object-upload-form">
            <form action="./photos.php?act=upload" method="post" target="_blank" enctype="multipart/form-data">
                <input type="file" id="photos-object-upload-file" name="photo" accept="image/jpeg,image/png" />
                <input type="hidden" name="pcid" value="0" />
                <input type="hidden" name="pcid_add" value="0" />
                <input type="hidden" name="ptid_add" value="1">
                <input type="hidden" name="mdid" value="0" />
                <input type="hidden" name="pgid" value="0" />
                <input type="hidden" id="photos-upload-bind-ptid" name="ptid" value="0">
                <input type="submit" id="photos-object-upload-button" value="Загрузить" />
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/css/admin/photos_import.css" type="text/css"/>
<script type="text/javascript" src="/js/admin/photos_import.js" defer="defer"></script>
