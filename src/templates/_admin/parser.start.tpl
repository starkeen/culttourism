<img class="pageicon" src="/img/admin/ico.a_refs.gif" />
<h3>{$title}</h3>

<div class="parser-start-form">
    <input type="text" class="parser-start-url" value="" />
    <input type="button" class="parser-start-run" value="Разобрать" />
</div>
<div class="parser-work-container m_hide">
    <input type="text" class="parser-work-region" value="" placeholder="регион" />
    <br /><br />
    <a href="#" class="parser-work-all">все</a>
    <table></table>
    <a href="#" class="parser-work-all">все</a>
    <br /><br /><br />
    <input type="hidden" class="parser-work-region-id" value="0" />
    <input type="button" class="parser-work-import" value="загрузить" />
    <br />
</div>

<script type="text/javascript" src="/js/admin/parser.js" defer="defer"></script>
<style type="text/css">
    .parser-start-form {
        margin:5px;
    }
    .parser-start-url {
        width:350px;
    }
    .parser-work-container {
        padding: 5px;
    }
</style>