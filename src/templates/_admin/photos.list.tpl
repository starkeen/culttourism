<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div id="photos-to-flickr" class="photos-block">
    <a href="./photos_import.php">Импорт фото</a>
</div>

<div id="photos-upload" class="photos-block">
    <form action="?act=upload" method="post" enctype="multipart/form-data">
        Добавить фото
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" />
        <div id="photos-upload-data">
            <input type="text" name="title" placeholder="Название" />
            <input type="text" name="author" placeholder="Автор" />
            <input type="text" name="link" placeholder="Ссылка" />
        </div>
        <div id="photos-upload-bind">
            <input type="hidden" id="photos-upload-bind-pcid" name="pcid" value="0" />
            <input type="hidden" id="photos-upload-bind-ptid" name="ptid" value="0" />
            <input type="hidden" id="photos-upload-bind-mdid" name="mdid" value="0" />
            <input type="hidden" id="photos-upload-bind-pgid" name="pgid" value="0" />

            <input type="text" id="photos-upload-bind-pc" value="" placeholder="регион" />
            <input type="hidden" name="pcid_add" value="0" />
            <input type="checkbox" id="photos-upload-bind-pc-bind"
                   name="pcid_add"
                   title="Прикрепить к странице региона"
                   value="1" />
            <input type="button" id="photos-upload-bind-pc-clean" value="X" />
            
            &nbsp;&nbsp;&nbsp;&nbsp;
            
            <input type="text" id="photos-upload-bind-pt" value="" placeholder="объект" />
            <input type="hidden" name="ptid_add" value="0" />
            <input type="checkbox" id="photos-upload-bind-pt-bind"
                   name="ptid_add"
                   title="Прикрепить к странице объекта"
                   checked="checked"
                   value="1" />
            <input type="button" id="photos-upload-bind-pt-clean" value="X" />
        </div>
        <input type="submit" value="Загрузить" />
    </form>
</div>

<div id="photos-list" class="photos-block">
    <form action="" method="get">
        <div class="photos-listpager">{$pager}</div>

        <table class="commontable">
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Автор</th>
                <th>Ссылка</th>
                <th>Размеры</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <th>
                    <input type="text" name="fid" value="{$get.fid}" placeholder="ID" style="width: 40px;" />
                </th>
                <th>
                    <input type="text" name="ftitle"
                           value="{$get.ftitle}"
                           id="photos-listfilter-title"
                           placeholder="название" />
                    <input type="button" id="photos-listfilter-title-clean" value="X" />
                    <br />
                    <input type="text" name="fregion"
                           value="{$get.fregion}"
                           id="photos-listfilter-region"
                           placeholder="регион" />
                    <input type="button" id="photos-listfilter-region-clean" value="X" />
                    <br />
                    <input type="text" name="fobject"
                           value="{$get.fobject}"
                           id="photos-listfilter-object"
                           placeholder="объект" />
                    <input type="button" id="photos-listfilter-object-clean" value="X" />
                    <br />
                    <input type="hidden" name="fregionid"
                           id="photos-listfilter-regionid"
                           value="{$get.fregionid}" />
                    <input type="hidden" name="fobjectid"
                           id="photos-listfilter-objectid"
                           value="{$get.fobjectid}" />
                </th>
                <th>
                    <input type="text" name="fauthor" value="{$get.fauthor}" placeholder="автор" />
                </th>
                <th>
                    <input type="text" name="flink" value="{$get.flink}" placeholder="ссылка" />
                </th>
                <th>&nbsp;</th>
                <th>
                    <input type="submit" value="искать" />
                </th>
            </tr>
            {foreach from=$list.items item=ph}
            <tr class="photos-listitem">
                <td class="m_center">{$ph.ph_id}</td>
                <td>
                    {$ph.ph_title}
                    <span class="photos-listitem-region">{$ph.pc_title}</span>
                    <span class="photos-listitem-object">{$ph.pt_name}</span>
                </td>
                <td>{$ph.ph_author}</td>
                <td>
                    {if $ph.ph_link}
                        <a href="{$ph.ph_link}" target="_blank">
                            {$ph.ph_link|truncate:25:"…"}
                        </a>
                    {/if}
                </td>
                <td>
                    {$ph.ph_width}x{$ph.ph_height}
                    <br>
                    {round($ph.ph_weight / 1024)} kB
                    <br>
                    {$ph.ph_mime}
                </td>
                <td>
                    <a href="?id={$ph.ph_id}"><img src="{$ph.ph_src}" style="max-height: 50px" /></a>
                </td>
            </tr>
            {/foreach}
        </table>

        <div class="photos-listpager">{$pager}</div>
    </form>
</div>

<link rel="stylesheet" href="/css/admin/photos.css" type="text/css" />
<script type="text/javascript" src="/js/admin/photos.js" defer="defer"></script>
