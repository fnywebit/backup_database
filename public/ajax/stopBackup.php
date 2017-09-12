<?php

if (!defined('ABSPATH')) {
	die;
}

$retrieved_nonce = $_POST['_ajax_nonce'];
if (!wp_verify_nonce($retrieved_nonce, 'fny-backup-ajax-nonce')) {
	die('Failed security check');
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupDump.php');

if(isset($_POST['id'])) {
	$id = (int)$_POST['id'];
	FNYBackupDump::cancelAction($id);
	die('1');
}
