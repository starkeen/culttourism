FCKConfig.ToolbarSets["str"] = [
	['Save','-','FitWindow','Source','-','Preview'],
	['Undo','Redo'],
	['Cut','Copy','Paste','PasteText','PasteWord'],
	['Link','Unlink'],
	['Image','Table','Rule','SpecialChar'],
	'/',
	['FontSize', 'Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote'],
	['TextColor','BGColor'] // No comma for the last row.
];
FCKConfig.LinkBrowser = false;
FCKConfig.LinkUpload = true;
FCKConfig.DefaultLanguage = 'ru' ;
FCKConfig.ImageUploadAllowedExtensions = ".(jpg|gif|jpeg|png)$";

FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/office2003/';