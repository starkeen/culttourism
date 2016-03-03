CKEDITOR.editorConfig = function (config) {
    // config.uiColor = '#AADC6E';
    config.height = 230;
    config.skin = 'office2003';
    config.toolbar = 'Main';
    config.language = 'ru';
    config.contentsCss = document.location.protocol + '//' + document.location.host + '/css/editors.css';

    config.toolbar_Main =
            [
                ['Source'],
                ['Cut', 'Copy', 'Paste', 'PasteFromWord'],
                ['Undo', 'Redo'],
                ['Bold', 'Italic', 'Subscript', 'Superscript'],
                ['NumberedList', 'BulletedList'],
                ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
                ['Link', 'Unlink']
            ];

    config.toolbar_City =
            [
                ['Source'],
                ['Cut', 'Copy', 'Paste', 'PasteFromWord'],
                ['Undo', 'Redo'],
                ['Bold', 'Italic', 'Subscript', 'Superscript'],
                ['NumberedList', 'BulletedList'],
                ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
                ['Link', 'Unlink', 'Image']
            ];
    config.toolbar_Blog =
            [
                ['Source'],
                ['Cut', 'Copy', 'Paste', 'PasteFromWord'],
                ['Undo', 'Redo'],
                ['Bold', 'Italic', 'Subscript', 'Superscript'],
                ['NumberedList', 'BulletedList'],
                ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
                ['Link', 'Unlink', 'Image']
            ];
};