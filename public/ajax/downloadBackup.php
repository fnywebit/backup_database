<?php

if (!defined('ABSPATH')) {
	die;
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupDump.php');

if (count($_GET)) {
	try {
		$name = sanitize_text_field($_GET['name']);
		$type = sanitize_text_field($_GET['type']);

		FNYBackupDump::downloadBackup($name, $type);
	}
	catch (Exception $e) {

	}
}
