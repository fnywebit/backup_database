<?php

if (!defined('ABSPATH')) {
	die;
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYFTP.php');

if (count($_POST)) {
	$_POST = wp_unslash($_POST);
	$_POST = array_map('stripslashes', $_POST);

	if(isset($_POST['cancel'])) {
		FNYBackupConfig::set('FNY_FTP_HOST', '');
		FNYBackupConfig::set('FNY_FTP_PORT', '');
		FNYBackupConfig::set('FNY_FTP_USER', '');
		FNYBackupConfig::set('FNY_FTP_PASSWORD', '');
		FNYBackupConfig::set('FNY_FTP_ROOT_FOLDER', '');
		FNYBackupConfig::set('FNY_FTP_CONNECTED', '');
		FNYBackupConfig::set('FNY_FTP_CONNECTION_STRING', '');

		die("success");
	}

	$options = $_POST;
	$error = array();
	$success = 'success';

	if(!isset($options['fnyFtpHost'])) {
		array_push($error, 'Host field is required.');
	}
	if(!isset($options['fnyFtpPort'])) {
		array_push($error, 'Port field is required.');
	}
	if(!isset($options['fnyFtpUser'])) {
		array_push($error, 'User field is required.');
	}
	if(!isset($options['fnyRootFolder'])) {
		array_push($error, 'Root directory field is required.');
	}
	if(!isset($options['fnyFtpPassword'])) {
		array_push($error, 'Password field is required.');
	}

	//If there are errors do not continue
	if(count($error)) {
		die(json_encode($error));
	}

	//Try to connect
	try {
		FNYBackupConfig::set('FNY_FTP_HOST',$options['fnyFtpHost']);
		FNYBackupConfig::set('FNY_FTP_PORT',$options['fnyFtpPort']);
		FNYBackupConfig::set('FNY_FTP_USER',$options['fnyFtpUser']);
		FNYBackupConfig::set('FNY_FTP_PASSWORD',$options['fnyFtpPassword']);
		FNYBackupConfig::set('FNY_FTP_ROOT_FOLDER',$options['fnyRootFolder']);
		FNYBackupConfig::set('FNY_FTP_CONNECTION_STRING', $options['fnyFtpString']);

		$storage = new FNYFTP();
		$storage->connect();

		FNYBackupConfig::set('FNY_FTP_CONNECTED', '1');
		die($success);
	}
	catch(Exception $exception) {
		FNYBackupConfig::set('FNY_FTP_CONNECTED', '');
		array_push($error, $exception->getMessage());
		die(json_encode($error));
	}
}
