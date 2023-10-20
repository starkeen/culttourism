<?php

session_start();
/*
 * ### CKFinder : Configuration File - Basic Instructions
 *
 * In a generic usage case, the following tasks must be done to configure
 * CKFinder:
 *     1. Check the $baseUrl and $baseDir variables;
 *     2. If available, paste your license key in the "LicenseKey" setting;
 *     3. Create the CheckAuthentication() function that enables CKFinder for authenticated users;
 *
 * Other settings may be left with their default values, or used to control
 * advanced features of CKFinder.
 */

/**
 * This function must check the user session to be sure that he/she is
 * authorized to upload and access files in the File Browser.
 *
 * @return boolean
 */
function CheckAuthentication()
{
    return (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0);
}

// LicenseKey : Paste your license key here. If left blank, CKFinder will be
// fully functional, in demo mode.
$config['LicenseName'] = '';
$config['LicenseKey'] = '';

$baseUrl = '/data/';

$baseDir = resolveUrl($baseUrl);

/*
 * ### Advanced Settings
 */

$config['Thumbnails'] = [
    'url' => $baseUrl . '_thumbs',
    'directory' => $baseDir . '_thumbs',
    'enabled' => true,
    'directAccess' => false,
    'maxWidth' => 100,
    'maxHeight' => 100,
    'bmpSupported' => false,
    'quality' => 80,
];

$config['Images'] = [
    'maxWidth' => 1600,
    'maxHeight' => 1200,
    'quality' => 80,
];

$config['RoleSessionVar'] = 'CKFinder_UserRole';

$config['AccessControl'][] = [
    'role' => '*',
    'resourceType' => '*',
    'folder' => '/',
    'folderView' => true,
    'folderCreate' => true,
    'folderRename' => true,
    'folderDelete' => true,
    'fileView' => true,
    'fileUpload' => true,
    'fileRename' => true,
    'fileDelete' => true,
];

$config['DefaultResourceTypes'] = '';

$config['ResourceType'][] = [
    'name' => 'Files', // Single quotes not allowed
    'url' => $baseUrl . 'file',
    'directory' => $baseDir . 'files',
    'maxSize' => 0,
    'allowedExtensions' => '7z,aiff,asf,avi,bmp,csv,doc,docx,fla,flv,gif,gz,gzip,jpeg,jpg,mid,mov,mp3,mp4,mpc,mpeg,mpg,ods,odt,pdf,png,ppt,pptx,pxd,qt,ram,rar,rm,rmi,rmvb,rtf,sdc,sitd,swf,sxc,sxw,tar,tgz,tif,tiff,txt,vsd,wav,wma,wmv,xls,xlsx,zip,webp',
    'deniedExtensions' => '',
];

$config['ResourceType'][] = [
    'name' => 'Images',
    'url' => $baseUrl . 'images',
    'directory' => $baseDir . 'images',
    'maxSize' => 0,
    'allowedExtensions' => 'bmp,gif,jpeg,jpg,png,webp',
    'deniedExtensions' => '',
];

$config['CheckDoubleExtension'] = true;

$config['DisallowUnsafeCharacters'] = false;

$config['FilesystemEncoding'] = 'UTF-8';

$config['SecureImageUploads'] = true;

$config['CheckSizeAfterScaling'] = true;

$config['HtmlExtensions'] = ['html', 'htm', 'xml', 'js'];

$config['HideFolders'] = [".*", "CVS"];

$config['HideFiles'] = [".*"];

$config['ChmodFiles'] = 0777;

$config['ChmodFolders'] = 0755;

$config['ForceAscii'] = false;

$config['XSendfile'] = false;

include_once "plugins/imageresize/plugin.php";
include_once "plugins/fileeditor/plugin.php";
include_once "plugins/zip/plugin.php";

$config['plugin_imageresize']['smallThumb'] = '90x90';
$config['plugin_imageresize']['mediumThumb'] = '120x120';
$config['plugin_imageresize']['largeThumb'] = '180x180';
