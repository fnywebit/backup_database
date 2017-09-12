<?php

class FNYBackupCore
{
	public static function install()
	{
		self::installTables();
		self::createBackupFolder();
	}

	private static function createBackupFolder()
	{
		if (!is_dir(FNY_DATABASE_BACKUP_FOLDER_PATH)) {
			if (!mkdir(FNY_DATABASE_BACKUP_FOLDER_PATH)) {
				throw new Exception('Uploads directory is not writable');
			}
		}
	}

	private static function installTables()
	{
		self::installBackupTable();
		self::installConfigTable();
	}

	private static function installBackupTable()
	{
		global $wpdb;

		$wpdb->query('DROP TABLE IF EXISTS `'.$wpdb->prefix.'fny_backups`;');

		$fnytablestruct = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."fny_backups` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL,
			`type` tinyint(3) unsigned NOT NULL,
			`status` tinyint(3) unsigned NOT NULL,
			`progress` int(11) unsigned,
			`options` text NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8; ";

		$res = $wpdb->query($fnytablestruct);

		if ($res === false) {
			throw new Exception('Could not install backup table');
		}
	}

	private static function installConfigTable()
	{
		global $wpdb;

		$wpdb->query('DROP TABLE IF EXISTS `'.$wpdb->prefix.'fny_configs`;');

		$res = $wpdb->query(
			'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'fny_configs` (
			  `ckey` varchar(100) NOT NULL,
			  `cvalue` text NOT NULL,
			  PRIMARY KEY (`ckey`)
			) DEFAULT CHARSET=utf8;'
		);

		if ($res===false) {
			throw new Exception('Could not install config table');
		}
	}

	public static function uninstall()
	{
		self::uninstallTables();
	}

	private static function uninstallTables()
	{
		self::uninstallConfigsTable();
		self::uninstallBackupsTable();
	}

	private static function uninstallConfigsTable()
	{
		global $wpdb;

		$fnydroptable = "DROP TABLE IF EXISTS ".$wpdb->prefix."fny_configs";

		$res = $wpdb->query($fnytablestruct);

		if ($res === false) {
			throw new Exception('Could not uninstall configs table');
		}
	}

	private static function uninstallBackupsTable()
	{
		global $wpdb;

		$fnydroptable = "DROP TABLE IF EXISTS ".$wpdb->prefix."fny_backups";

		$res = $wpdb->query($fnytablestruct);

		if ($res === false) {
			throw new Exception('Could not uninstall backups table');
		}
	}
}
