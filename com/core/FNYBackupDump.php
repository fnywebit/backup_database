<?php

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupHelper.php');
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYDatabase.php');
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYDatabaseAdapter.php');
require_once(FNY_DATABASE_BACKUP_INCLUDS_PATH.'FNYBackupMysqldump.php');
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupUpload.php');

@include_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYMigrate.php');
@include_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYEmailNotification.php');

class FNYBackupDump
{
	private $dumpFileName = '';
	private $progress = 0;
	private $totalRowsCount = 0;
	private $tables = array();
	private $tablesToExclude = array();
	private $currentRunningActionId = null;
	private $nextProgressUpdateInterval = 5;
	private $errorsFound = false;

	function __construct()
	{
		try {
			$this->prepareForDump();
		}
		catch (Exception $e) {
			file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', $e->getMessage()."\n", FILE_APPEND);
		}
	}

	public function dumpDatabase()
	{
		try {
			$dbHandler = FNYDatabase::getInstance();

			FNYBackupConfig::set('FNY_IS_RUNNING', '1');

			$fnyMysqlDump = new FNYBackupMysqldump(DB_NAME, 'mysql', $dbHandler, array(
				'skip-dump-date'=>true,
				'skip-comments'=>true,
				'add-drop-table'=>true,
				'no-autocommit'=>false,
				'single-transaction'=>false,
				'lock-tables'=>false,
				'default-character-set'=>DB_CHARSET,
				'add-locks'=>false
			));

			$fnyMysqlDump->setDelegate($this);
			$fnyMysqlDump->start(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName."/".$this->dumpFileName.".sql");

			$currentStatus = $this->getCurrentActionStatus();
			if ($currentStatus == FNY_DATABASE_BACKUP_CANCELLED) {
				$this->cancel();
			}

			$this->didFinishDump(FNY_DATABASE_BACKUP_FINISHED);
		}
		catch (Exception $e) {
			file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', $e->getMessage()."\n", FILE_APPEND);

			$this->didFinishDump(FNY_DATABASE_BACKUP_FINISHED_WITH_ERROR);
		}
	}

	private function uploadToCloudServices()
	{
		try {
			$storages = FNYBackupHelper::getPendingStorageUploads();
			if (count($storages)) {

				foreach ($storages as $storage) {
					$this->currentRunningActionId = $this->createBackupRow(FNY_DATABASE_BACKUP_TYPE_UPLOAD);

					FNYBackupUpload::getInstance()->setDelegate($this);
					FNYBackupUpload::getInstance()->setActionId($this->currentRunningActionId);
					FNYBackupUpload::getInstance()->startUpload($storage, $this->dumpFileName);

					$this->didFinishUpload(FNY_DATABASE_BACKUP_FINISHED);
				}
			}
		}
		catch(Exception $e) {
			file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', $e->getMessage()."\n", FILE_APPEND);

			$this->didFinishUpload(FNY_DATABASE_BACKUP_FINISHED_WARNINGS);
		}
	}

	private function cancel()
	{
		if ($this->dumpFileName && is_dir(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName)) {
			FNYBackupHelper::deleteFolder(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName);
		}

		throw new Exception("Dump canceled");
	}

	private function prepareForDump()
	{
		$this->setDumpFileName();
		$this->prepareBackupFolder();
		$this->prepareLogFile();

		$this->tables = $this->getAllTablesFromDatabase();
		$this->totalRowsCount = $this->getTotalRowsCountInTables($this->tables);
		$this->currentRunningActionId = $this->createBackupRow(FNY_DATABASE_BACKUP_TYPE_DUMP);
	}

	private function prepareBackupFolder()
	{
		if (!is_dir(FNY_DATABASE_BACKUP_FOLDER_PATH)) {
			if (!mkdir(FNY_DATABASE_BACKUP_FOLDER_PATH)) {
				throw new Exception('Uploads directory is not writable');
			}
		}

		if (!mkdir(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName)) {
			throw new Exception('Uploads directory is not writable');
		}
	}

	private function prepareLogFile()
	{
		$headers = $this->getLogFileHeader();

		file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', $headers."\n");
	}

	private function getAllTablesFromDatabase()
	{
		global $wpdb;

		$tableNames = array();
		$tables = $wpdb->get_results('SHOW TABLES FROM '.DB_NAME, ARRAY_A);

		if (!$tables) {
			throw new Exception('Could not get tables of database: '.DB_NAME);
		}

		foreach ($tables as $table) {
			$tableName = $table['Tables_in_'.DB_NAME];
			$tableNames[] = $tableName;
		}

		return $tableNames;
	}

	private function getTotalRowsCountInTables($tables = array())
	{
		$totalRowsCount = 0;
		foreach ($tables as $table) {
			$totalRowsCount += $this->getTableRowsCount($table);
		}

		return $totalRowsCount;
	}

	private function getTableRowsCount($table)
	{
		global $wpdb;

		$count = 0;
		$tableRowsCount = $wpdb->get_results('SELECT COUNT(*) AS total FROM '.$table, ARRAY_A);
		$count = @$tableRowsCount[0]['total'];

		return $count;
	}

	private function createBackupRow($type)
	{
		global $wpdb;

		$options = sanitize_text_field(json_encode(array()));

		$query = "INSERT INTO ".$wpdb->prefix."fny_backups (name, type, status, options) VALUES (%s, %d, %d, %s)";
		$res = $wpdb->query($wpdb->prepare($query, array($this->dumpFileName, $type, FNY_DATABASE_BACKUP_INPROGRESS, $options)));

		if (!$res) {
			throw new Exception('Could not create action');
		}

		return $wpdb->insert_id;
	}

	private function setDumpFileName()
	{
		$prefix = FNYBackupConfig::get("FNY_DATABASE_BACKUP_FILE_PREFIX")?FNYBackupConfig::get("FNY_DATABASE_BACKUP_FILE_PREFIX"):FNY_DATABASE_BACKUP_DEFAULF_PREFIX;
		$this->dumpFileName = $prefix.date("Y-m-d H-i-s");
	}

	private function shouldUpdateProgress($progress)
	{
		if ($progress >= $this->nextProgressUpdateInterval) {
			$this->nextProgressUpdateInterval += FNY_DATABASE_BACKUP_PROGRESS_UPDATE_INTERVAL;
			return true;
		}

		return false;
	}

	public function updateProgress($progress, $id)
	{
		global $wpdb;

		$query = 'UPDATE '.$wpdb->prefix.'fny_backups SET progress=%d WHERE id=%d';
		$res = $wpdb->query(
			$wpdb->prepare($query, array((int)$progress, (int)$id))
		);
	}

	private function getCurrentActionStatus()
	{
		return self::getActionStatusById($this->currentRunningActionId);
	}

	private function isCancelled()
	{
		$status = $this->getCurrentActionStatus();

		if ($status == FNY_DATABASE_BACKUP_CANCELLED) {
			$this->cancel();
		}
	}

	private function getLogFileHeader()
	{
		global $wpdb;
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];

		$confs = array();
		$confs['os'] = PHP_OS;
		$confs['server'] = @$_SERVER['SERVER_SOFTWARE'];
		$confs['php_version'] = PHP_VERSION;
		$confs['sapi'] = PHP_SAPI;
		$confs['mysql_version'] = $wpdb->db_version();
		$confs['int_size'] = PHP_INT_SIZE;
		$confs['dbprefix'] = $wpdb->prefix;
		$confs['siteurl'] = get_site_url();
		$confs['uploadspath'] = $upload_dir;

		$confs['memory_limit'] = ini_get('memory_limit');
		$confs['max_execution_time'] = ini_get('max_execution_time');

		$content = '';
		$content .= 'Date: '.@date('Y-m-d H:i').PHP_EOL;
		$content .= 'Database prefix: '.$confs['dbprefix'].PHP_EOL;
		$content .= 'Site URL: '.$confs['siteurl'].PHP_EOL;
		$content .= 'Uploads path: '.$confs['uploadspath'].PHP_EOL;

		$content .= 'OS: '.$confs['os'].PHP_EOL;
		$content .= 'Server: '.$confs['server'].PHP_EOL;
		$content .= 'User agent: '.@$_SERVER['HTTP_USER_AGENT'].PHP_EOL;
		$content .= 'PHP version: '.$confs['php_version'].PHP_EOL;
		$content .= 'SAPI: '.$confs['sapi'].PHP_EOL;
		$content .= 'MySQL version: '.$confs['mysql_version'].PHP_EOL;
		$content .= 'Int size: '.$confs['int_size'].PHP_EOL;
		$content .= 'Memory limit: '.$confs['memory_limit'].PHP_EOL;
		$content .= 'Max execution time: '.$confs['max_execution_time'].PHP_EOL;

		return $content;
	}

	/****** Static methods ******/
	// returns array of backups
	public static function getAllBackups()
	{
		global $wpdb;

		FNYBackupHelper::deleteOutdatedBackups();

		$backups = array();
		$allBackups = array();
		clearstatcache();

		if ($handle = @opendir(FNY_DATABASE_BACKUP_FOLDER_PATH)) {
			$query = 'SELECT id, name, status, options FROM '.$wpdb->prefix.'fny_backups WHERE status<>%d';
			$data = $wpdb->get_results($wpdb->prepare($query, array(FNY_DATABASE_BACKUP_CANCELLED)), ARRAY_A);

			foreach ($data as $row) {
				$backups[$row['name']][] = $row;
			}

			while (($entry = readdir($handle)) !== false) {
				if ($entry === '.' || $entry === '..' || !is_dir(FNY_DATABASE_BACKUP_FOLDER_PATH.$entry)) {
					continue;
				}

				$backup = array();
				$backup['name'] = $entry;
				$backup['id'] = '';
				$backup['status'] = '';
				$backup['backup'] = file_exists(FNY_DATABASE_BACKUP_FOLDER_PATH.$entry.'/'.$entry.'.sql.zip')?1:0;
				$backup['log'] = file_exists(FNY_DATABASE_BACKUP_FOLDER_PATH.$entry.'/'.$entry.'.log')?1:0;
				$backup['options'] = '';

				if (!$backup['backup'] && !$backup['log']) {
					continue;
				}

				$backupRow = null;
				if (isset($backups[$entry])) {
					$skip = false;
					foreach ($backups[$entry] as $row) {
						if ($row['status'] == FNY_DATABASE_BACKUP_INPROGRESS) {
							$backupRow = $row;
							break;
						}
						else if ($row['status'] == FNY_DATABASE_BACKUP_CANCELLED) {
							$skip = true;
							break;
						}

						$backupRow = $row;
					}

					if ($skip === true) {
						continue;
					}
				}

				if ($backupRow) {
					$backup['active'] = ($backupRow['status'] == FNY_DATABASE_BACKUP_INPROGRESS)?1:0;

					$backup['id'] = (int)$backupRow['id'];
					$backup['status'] = $backupRow['status'];
					$backup['options'] = $backupRow['options'];
				}
				else {
					$backup['active'] = 0;
				}

				$size = '';
				if ($backup['backup']) {
					$size = number_format(filesize(FNY_DATABASE_BACKUP_FOLDER_PATH.$entry.'/'.$entry.'.sql.zip')/1000, 2, '.', '').' KB';
				}

				$backup['size'] = $size;
				$allBackups[] = $backup;
			}

			closedir($handle);
		}

		return array_values($allBackups);
	}

	public static function deleteBackupByName($name)
	{
		global $wpdb;

		FNYBackupHelper::deleteFolder(FNY_DATABASE_BACKUP_FOLDER_PATH.$name);

		$query = 'DELETE FROM '.$wpdb->prefix.'fny_backups WHERE name=%s';
		$result = $wpdb->query($wpdb->prepare($query, array($name)));

		return $result;
	}

	public static function cancelAction($id)
	{
		self::updateActionStatus($id, FNY_DATABASE_BACKUP_CANCELLED);
	}

	private static function updateActionStatus($id, $status)
	{
		global $wpdb;

		$query = "UPDATE ".$wpdb->prefix."fny_backups SET status=%d WHERE id=%d";
		$wpdb->query($wpdb->prepare($query, array($status, $id)));
	}

	public static function getActionById($id)
	{
		global $wpdb;

		$query = "SELECT * FROM ".$wpdb->prefix."fny_backups WHERE id=%d";
		$res = $wpdb->get_results($wpdb->prepare($query, array($id)), ARRAY_A);

		if (empty($res)) {
			return false;
		}

		return $res[0];
	}

	public static function getActionStatusById($id)
	{
		global $wpdb;

		$query = 'SELECT status FROM '.$wpdb->prefix.'fny_backups WHERE id=%d';
		$res = $wpdb->get_results($wpdb->prepare($query, array($id)), ARRAY_A);
		if (empty($res)) {
			return false;
		}

		return (int)$res[0]['status'];
	}

	public static function getActiveBackupId()
	{
		global $wpdb;

		$query = "SELECT id FROM ".$wpdb->prefix."fny_backups WHERE status=%d";
		$res = $wpdb->get_results($wpdb->prepare($query, array(FNY_DATABASE_BACKUP_INPROGRESS)), ARRAY_A);

		if (empty($res)) {
			return false;
		}

		return $res[0];
	}

	public static function downloadBackup($name, $type)
	{
		$directory = FNY_DATABASE_BACKUP_FOLDER_PATH.$name.'/';

		switch ($type) {
			case "log":
				$name .= '.log';
				FNYBackupHelper::downloadFile($directory.$name, 'text/plain');
				break;
			case "backup":
				$name .= '.sql.zip';
				FNYBackupHelper::downloadFile($directory.$name, 'application/zip');
				break;
		}

		exit;
	}

	private function sendMailNotification($subject, $vars = array())
	{
		$emailNotification = new FNYEmailNotification();

		$emailNotification->setSubject();
		$emailNotification->setTemplate();
		$emailNotification->setTemplateVariables($vars);
		$emailNotification->setFrom(FNYBackupConfig::get('FNY_DATABASE_BACKUP_EMAIL_NOTIFICATION'));
		$emailNotification->setTo(FNYBackupConfig::get('FNY_DATABASE_BACKUP_EMAIL_NOTIFICATION'));

		return $emailNotification->send();
	}

	/****** Delegate methods ******/
	public function didDumpRow()
	{
		$this->progress++;
		$progress = ($this->progress*100)/$this->totalRowsCount;

		$this->isCancelled();

		if ($this->shouldUpdateProgress($progress)) {
			$this->updateProgress($progress, $this->currentRunningActionId);
		}
	}

	public function didDumpTable($table)
	{
		file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', "Start dumping table: ".$table."\n", FILE_APPEND);
	}

	private function didFinishDump($status)
	{
		FNYBackupConfig::set('FNY_IS_RUNNING', '0');
		self::updateActionStatus($this->currentRunningActionId, $status);

		$migrateTo = FNYBackupConfig::get("FNY_DATABASE_BACKUP_MIGRATE_TO");
		$path = FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.sql';
		if (FNY_DATABASE_BACKUP_MIGRATION_AVAILABLE && $migrateTo) {
			file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', "Starting migration process:\n", FILE_APPEND);

			$fnyDatabaseMigrate = new FNYMigrate();

			$fnyDatabaseMigrate->setTo($migrateTo);
			$fnyDatabaseMigrate->setPath($path);
			$fnyDatabaseMigrate->runMigration();

			file_put_contents(FNY_DATABASE_BACKUP_FOLDER_PATH.$this->dumpFileName.'/'.$this->dumpFileName.'.log', "End migration process:\n", FILE_APPEND);
		}

		$this->compressDatabaseBackup($path);

		if ($status != FNY_DATABASE_BACKUP_FINISHED_WITH_ERROR) {
			$this->uploadToCloudServices();
		}
	}

	private function compressDatabaseBackup($filepath)
	{
		$z = new ZipArchive();
		$z->open($filepath.".zip", ZIPARCHIVE::CREATE);
		$z->addFile($filepath);
		$z->close();

		@unlink($filepath);
	}

	private function didFinishUpload($status)
	{
		FNYBackupConfig::set('FNY_IS_RUNNING', '0');
		self::updateActionStatus($this->currentRunningActionId, $status);
	}
}
