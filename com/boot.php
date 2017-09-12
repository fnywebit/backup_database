<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once(dirname(__FILE__).'/config/config.php');
require_once(dirname(__FILE__).'/config/config.free.php');
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupCore.php');
