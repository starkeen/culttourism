CKEDITOR.editorConfig = function (config) {
    config.height = '400px';
    config.language = 'ru';
    config.uiColor = '#F0FFFF';

    config.toolbar_Full =
            [
                {name: 'document', items: ['Source', '-', 'Save', 'NewPage', 'DocProps', 'Preview', 'Print', '-', 'Templates']},
                {name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
                {name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt']},
                {name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton',
                        'HiddenField']},
                '/',
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv',
                        '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl']},
                {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
                {name: 'insert', items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']},
                '/',
                {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                {name: 'colors', items: ['TextColor', 'BGColor']},
                {name: 'tools', items: ['Maximize', 'ShowBlocks', '-', 'About']}
            ];
    config.toolbar_Main =
            [
                {name: 'document', items: ['Source', '-', 'DocProps']},
                {name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']},
                {name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt']},
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv',
                        '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']},
                '/',
                {name: 'links', items: ['Link', 'Unlink', 'Anchor']},
                {name: 'insert', items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']},
                {name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize']},
                {name: 'colors', items: ['TextColor', 'BGColor']},
                {name: 'tools', items: ['Maximize', 'ShowBlocks']}
            ];
    config.toolbar_Lite =
            [
                {name: 'document', items: ['Source']},
                {name: 'clipboard', items: ['PasteFromWord', '-', 'Undo', 'Redo']},
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList']},
                {name: 'links', items: ['Link', 'Unlink']},
                {name: 'insert', items: ['Image', 'Table']},
                {name: 'styles', items: ['Format', 'FontSize']}
            ];
    config.toolbar_Blog =
            [
                {name: 'document', items: ['Source']},
                {name: 'clipboard', items: ['PasteFromWord', '-', 'Undo', 'Redo']},
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList', 'JustifyLeft', 'JustifyCenter', 'JustifyRight']},
                {name: 'links', items: ['Link', 'Unlink']},
                {name: 'insert', items: ['Image', 'Table']},
                {name: 'styles', items: ['Format', 'FontSize']}
            ];
    config.toolbar_City =
            [
                {name: 'document', items: ['Source']},
                {name: 'clipboard', items: ['PasteFromWord', '-', 'Undo', 'Redo']},
                {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline']},
                {name: 'paragraph', items: ['NumberedList', 'BulletedList']},
                {name: 'links', items: ['Link', 'Unlink']},
                {name: 'insert', items: ['Image', 'Table']},
                {name: 'styles', items: ['Format', 'FontSize']}
            ];
    config.toolbar = 'Blog';
    config.extraPlugins = 'justify';
    config.stylesSet = [];
    config.contentsCss = '/css/editors.css';
    config.disableNativeSpellChecker = false;
    config.filebrowserBrowseUrl = '/addons/ckfinder.2.4/ckfinder.html';
    config.filebrowserImageBrowseUrl = '/addons/ckfinder.2.4/ckfinder.html?type=Images';
    config.filebrowserFlashBrowseUrl = '/addons/ckfinder.2.4/ckfinder.html?type=Flash';
    config.filebrowserUploadUrl = '/addons/ckfinder.2.4/core/connector/php/connector.php?command=QuickUpload&type=Files';
    config.filebrowserImageUploadUrl = '/addons/ckfinder.2.4/core/connector/php/connector.php?command=QuickUpload&type=Images';
    config.filebrowserFlashUploadUrl = '/addons/ckfinder.2.4/core/connector/php/connector.php?command=QuickUpload&type=Flash';
};
CKEDITOR.stylesSet.add('my_styles', [
    // Block-level styles
    {name: 'Blue Title', element: 'h2', styles: {'color': 'Blue'}},
    {name: 'Red Title', element: 'h3', styles: {'color': 'Red'}},
    // Inline styles
    {name: 'CSS Style', element: 'span', attributes: {'class': 'my_style'}},
    {name: 'Marker: Yellow', element: 'span', styles: {'background-color': 'Yellow'}}
]);