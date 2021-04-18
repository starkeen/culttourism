<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div class="points-menu-block">
    <a href="./points.php">Точки</a>
    <a href="./links.php">Ссылки</a>
</div>

<div class="points-redirects-form-add" style="padding: 10px">
    Добавить редирект
    <form method="post" action="?act=upload">
        <label>
            Откуда
            <input type="text" name="from" style="width:400px;" autocomplete="off" />
        </label>
        <br />
        <br />
        <label>
            Куда
            <input type="text" name="to" style="width:400px;" autocomplete="off" />
        </label>
        <br />
        <br />
        <input type="submit" value="Добавить" />
    </form>
</div>

<div class="points-redirects-list"></div>

<link rel="stylesheet" href="/css/admin/points.css" type="text/css" />
