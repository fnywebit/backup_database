<?php

if (!defined('ABSPATH')) {
	die;
}

$retrieved_nonce = $_POST['_ajax_nonce'];
if (!wp_verify_nonce($retrieved_nonce, 'fny-backup-ajax-nonce')) {
	die('Failed security check');
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupDump.php');

if(count($_POST)) {
	@session_write_close();

	$id = (int)$_POST['id'];
	$action = FNYBackupDump::getActionById($id);

	if($action) {
		if($action['status'] == FNY_DATABASE_BACKUP_INPROGRESS) {
			die(json_encode($action));
		}
		die('0');
	}
	die('0');
}
