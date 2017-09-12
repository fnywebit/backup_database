<?php

if (!defined('ABSPATH')) {
	die;
}

@session_start();
unset($_SESSION['fny_database_backup_gdrive_access_token']);
unset($_SESSION['fny_database_backup_gdrive_expiration_ts']);

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYGDrive.php');

FNYBackupConfig::set('FNY_DATABASE_BACKUP_GDRIVE_REFRESH_TOKEN', '');
FNYBackupConfig::set('FNY_DATABASE_BACKUP_GDRIVE_CONNECTION_STRING', '');

if(isset($_POST['cancel'])) {

	die("success");
}

$fnyGDrive = new FNYGDrive();
$fnyGDrive->connect();

if($fnyGDrive->isConnected()) {
	header("Location: ".FNY_DATABASE_BACKUP_ADMIN_URL);
	exit();
}
