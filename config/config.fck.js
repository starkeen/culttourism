FCKConfig.ToolbarSets["str"] = [
	['Source'],
	['Undo','Redo'],
	['Cut','Copy','Paste','PasteText','PasteWord'],
	['Link','Unlink'],
	['Image','Table'],
	['Style','Bold','Italic','Underline','-','Subscript','Superscript'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['OrderedList','UnorderedList'],
	['TextColor','BGColor'] // No comma for the last row.
];
FCKConfig.LinkBrowser = true;
FCKConfig.LinkUpload = true;
FCKConfig.DefaultLanguage = 'ru' ;
FCKConfig.ImageUploadAllowedExtensions = ".(jpg|gif|jpeg|png)$";

FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/office2003/';
FCKConfig.EditorAreaCSS = document.location.protocol + '//' + document.location.host + '/css/editors.css';
FCKConfig.StylesXmlPath = document.location.protocol + '//' + document.location.host + '/config/styles.fck.xml';