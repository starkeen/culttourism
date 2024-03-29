<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div id="photos-item">
    <img src="{$photo.ph_src}" id="photos-item-img" alt="" />

    <form method="post" action="">
        <table>
            <tr>
                <th>ID</th>
                <td>{$photo.ph_id}</td>
                <th>Дата</th>
                <td>{$photo.ph_date_add}</td>
            </tr>
            <tr>
                <th colspan="2">Название</th>
                <td colspan="2">{$photo.ph_title}</td>
            </tr>
            <tr>
                <th colspan="2">Автор</th>
                <td colspan="2">{$photo.ph_author}</td>
            </tr>
            <tr>
                <th colspan="2">Ссылка</th>
                <td colspan="2">{$photo.ph_link}</td>
            </tr>
            <tr>
                <th colspan="2">Размеры</th>
                <td colspan="2">{$photo.ph_width}x{$photo.ph_height}</td>
            </tr>
            <tr>
                <th>Широта</th>
                <td>{$photo.ph_lat}</td>
                <th>Долгота</th>
                <td>{$photo.ph_lon}</td>
            </tr>
            <tr>
                <th colspan="2">Регион</th>
                <td colspan="2">
                    <input type="text" id="photos-item-region" value="{$photo.binds.pc}" />
                    <input type="hidden" name="region_id"
                           id="photos-item-region-id"
                           value="{$photo.ph_pc_id}" />
                    <input type="button" id="photos-item-region-clean" value="X" />

                    <label>
                        <input type="hidden" name="bind_region" value="0" />
                        <input type="checkbox" name="bind_region" value="1" />
                        привязать к региону
                    </label>
                </td>
            </tr>
            <tr>
                <th colspan="2">Объект</th>
                <td colspan="2">
                    <input type="text" id="photos-item-object" value="{$photo.binds.pt}" />
                    <input type="hidden" name="object_id"
                           id="photos-item-object-id"
                           value="{$photo.ph_pt_id}" />
                    <input type="button" id="photos-item-object-clean" value="X" />

                    <label>
                        <input type="hidden" name="bind_object" value="0" />
                        <input type="checkbox" name="bind_object" value="1" />
                        привязать к объекту
                    </label>
                </td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="referer" value="{$referer}" />
                    <input type="submit" value="сохранить" />
                    <a href="{$referer}">вернуться</a>
                </th>
            </tr>
        </table>
    </form>
</div>



<link rel="stylesheet" href="/css/admin/photos.css" type="text/css" />
<script type="text/javascript" src="/js/admin/photos.js" defer="defer"></script>