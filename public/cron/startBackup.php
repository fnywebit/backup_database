<?php

if (!defined('ABSPATH')) {
	die;
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupDump.php');

$fnybackup = new FNYBackupDump();
$fnybackup->dumpDatabase();
