<?php

if (!defined('ABSPATH')) {
	die;
}

$retrieved_nonce = $_POST['_ajax_nonce'];
if (!wp_verify_nonce($retrieved_nonce, 'fny-backup-ajax-nonce')) {
	die('Failed security check');
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');

if (count($_POST)) {
	$prefix = isset($_POST['prefix'])?sanitize_text_field($_POST['prefix']):FNY_DATABASE_BACKUP_DEFAULF_PREFIX;
	$retention = isset($_POST['retention'])?(int)$_POST['retention']:100;
	$email = isset($_POST['email'])?$_POST['email']:'';

	FNYBackupConfig::set("FNY_DATABASE_BACKUP_FILE_PREFIX", $prefix);
	FNYBackupConfig::set("FNY_DATABASE_BACKUP_RETENTION", $retention);
	FNYBackupConfig::set("FNY_DATABASE_BACKUP_EMAIL_NOTIFICATION", $email);
}

wp_die();
