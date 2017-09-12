<?php

require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYAmazon.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYGDrive.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYFTP.php');

class FNYBackupUpload
{
	private static $instance = null;

	private $currentUploadChunksCount = 0;
	private $totalUploadChunksCount = 0;
	private $progressUpdateInterval = 0;
	private $nextProgressUpdate = 0;
	private $delegate = null;
	private $backupName = '';
	private $currentUploadStorageId = null;
	private $actionId = null;

	private function __construct()
	{
		$this->progressUpdateInterval = FNY_DATABASE_BACKUP_PROGRESS_UPDATE_INTERVAL;
	}

	private function __clone()
	{

	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function getStorageObjectById($storageId, &$storageName = '')
	{
		$storageId = (int)$storageId;
		switch ($storageId) {
			case FNY_DATABASE_BACKUP_FTP:
				$storageName = 'FTP';
				return new FNYFTP();
				break;
			case FNY_DATABASE_BACKUP_GOOGLE_DRIVE:
				$storageName = 'Google Drive';
				return new FNYGDrive();
				break;
			case FNY_DATABASE_BACKUP_AMAZON:
				$storageName = 'Amazon S3';
				return new FNYAmazon();
				break;
		}

		throw new Exception('Unknown storage');
	}

	private function writeToLog($log)
	{
		$path = FNY_DATABASE_BACKUP_FOLDER_PATH.$this->backupName.'/'.$this->backupName.'.log';

		if (file_exists($path)) {
			file_put_contents($path, $log."\n", FILE_APPEND);
		}
	}

	public function setActionId($actionId)
	{
		$this->actionId = $actionId;
	}

	public function setDelegate($delegate)
	{
		$this->delegate = $delegate;
	}

	public function startUpload($storage, $backupName)
	{
		try {
			$this->backupName = $backupName;

			$storageObject = $this->getStorageObjectById($storage, $storageName);
			$log = "Start uploading to ".$storageName;
			$this->writeToLog($log);

			$storageObject->setDelegate($this);
			$storageObject->connectOffline();

			$this->writeToLog('Preparing folder');
			$activeDirectory = $storageObject->createFolder(FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME);

			$this->writeToLog('Uploading file');
			$path = FNY_DATABASE_BACKUP_FOLDER_PATH.$this->backupName.'/'.$this->backupName.'.sql.zip';

			$storageObject->setActiveDirectory($activeDirectory);
			$storageObject->uploadFile($path);

			$this->writeToLog('Finished uploading to '.$storageName);
		}
		catch (Exception $e) {
			$this->writeToLog($e->getMessage());
			throw $e;
		}

		return;
	}

	private function updateProgress($progress = null)
	{
		if (!$progress) {
			$progress = $this->currentUploadChunksCount*100.0/$this->totalUploadChunksCount;
		}

		if ($progress >= $this->nextProgressUpdate) {
			$this->nextProgressUpdate += $this->progressUpdateInterval;

			$progress = max($progress, 0);
			$progress = min($progress, 100);

			// update progress
			$this->delegate->updateProgress($progress, $this->actionId);

			return true;
		}

		return false;
	}

	private function checkCancellation()
	{
		$action = FNYBackupDump::getActionById($this->actionId);

		if ($action['status'] == FNY_DATABASE_BACKUP_CANCELLED) {
			$this->writeToLog('Upload cancelled');
			$this->deleteBackupFromStorage($this->currentUploadStorageId, $action['name']);
			throw new Exception('Upload cancelled');
		}
		elseif ($action['status'] == FNY_DATABASE_BACKUP_FINISHED_WITH_ERROR) {
			$this->writeToLog('Upload timeout error');
			$this->deleteBackupFromStorage($this->currentUploadStorageId, $action['name']);
			throw new Exception('Upload timeout error');
		}
	}

	// Delegate Methods
	public function willStartUpload($chunksCount)
	{
		$this->totalUploadChunksCount = $chunksCount;
	}

	public function shouldUploadNextChunk()
	{
		$this->currentUploadChunksCount++;
		if ($this->updateProgress()) {
			$this->checkCancellation();
		}

		return true;
	}

	public function updateProgressManually($progress)
	{
		if ($this->updateProgress($progress)) {
			$this->checkCancellation();
		}
	}
}
