<?php

if (!defined('ABSPATH')) {
	die;
}

$retrieved_nonce = $_POST['_ajax_nonce'];
if (!wp_verify_nonce($retrieved_nonce, 'fny-backup-ajax-nonce')) {
	die('Failed security check');
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');

while (true) {
	$created = FNYBackupConfig::get('FNY_IS_RUNNING', true);

	if ($created) {
		die('created');
	}
}
