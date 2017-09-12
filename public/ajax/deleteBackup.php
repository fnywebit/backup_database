<?php

if (!defined('ABSPATH')) {
	die;
}

$retrieved_nonce = $_POST['_ajax_nonce'];
if (!wp_verify_nonce($retrieved_nonce, 'fny-backup-ajax-nonce')) {
	die('Failed security check');
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupDump.php');

if(isset($_POST['name'])) {
	$name = $_POST['name'];

	if (is_array($name)) {
		foreach ($name as $backupName) {
			$backupName = sanitize_text_field($backupName);
			FNYBackupDump::deleteBackupByName($backupName);
		}
	}
	else {
		$name = sanitize_text_field($name);
		FNYBackupDump::deleteBackupByName($name);
	}
}

wp_die('1');
