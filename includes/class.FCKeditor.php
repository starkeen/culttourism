<?php
include(_DIR_ROOT.'/addons/fckeditor/fckeditor.php');

class MyFCK extends FCKeditor {
	
	public function __construct($instanceName) {
		parent::__construct($instanceName);
		$this->BasePath = '../addons/fckeditor/';
		$this->Config['CustomConfigurationsPath'] = '../../../config/config.fck.js';
		$this->ToolbarSet = 'str';
	}
	
}
?>