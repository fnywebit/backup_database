<?php

class FNYFTP
{
	private $connectionId = null;
	private $host;
	private $port;
	private $user;
	private $password;
	private $activeDirectory;
	private $delegate;
	private $offset = 0;
	const BUFFER_SIZE = 4194304; // 4mb

	public function __construct()
	{
		$this->host = FNYBackupConfig::get('FNY_FTP_HOST');
		$this->port = FNYBackupConfig::get('FNY_FTP_PORT');
		$this->user = FNYBackupConfig::get('FNY_FTP_USER');
		$this->password = FNYBackupConfig::get('FNY_FTP_PASSWORD');
		$this->activeDirectory = FNYBackupConfig::get('FNY_FTP_ROOT_FOLDER');
	}

	public function setDelegate($delegate)
	{
		$this->delegate = $delegate;
	}

	public function connect()
	{
		$connId = @ftp_connect($this->host, $this->port);
		if (!$connId) {
			throw new Exception('Could not connect to the FTP server: '.$this->host);
		}

		$login = @ftp_login($connId, $this->user, $this->password);
		if (!$login) {
			throw new Exception('Could not connect to the FTP server: '.$this->host);
		}

		//change default timeout to 60 seconds
		@ftp_set_option($connId, FTP_TIMEOUT_SEC, 60);

		//turn passive mode on
		@ftp_pasv($connId, true);

		FNYBackupConfig::set('FNY_FTP_CONNECTION_STRING', $this->user.'@'.$this->host.':'.$this->port);
		$this->connectionId = $connId;
	}

	public function getListOfFiles($rootPath)
	{
		return ftp_nlist($this->connectionId, $rootPath);
	}

	public function getFileSize($path)
	{
		return ftp_size($this->connectionId, $path);
	}

	public function getCreateDate($path)
	{
		return ftp_mdtm($this->connectionId, $path);
	}

	public function changeDirectory($directory)
	{
		return ftp_chdir($this->connectionId, $directory);
	}

	public function createFolder($path)
	{
		return @ftp_mkdir($this->connectionId, $path);
	}

	public function downloadFile($file, $size)
	{
		$loaclFilePath = FNY_DATABASE_BACKUP_FOLDER_PATH.basename($file);
		$serverFilePath = $file;

		$result = ftp_nb_get($this->connectionId, $loaclFilePath, $serverFilePath, FTP_BINARY);

		while ($result == FTP_MOREDATA) {
			if (!file_exists($loaclFilePath)) {
				break;
			}
			$result = ftp_nb_continue($this->connectionId);
		}

		return $result == FTP_FINISHED?true:false;
	}

	private function saveStateData($uploadId, $offset)
	{
		$this->delegate->saveStateData($uploadId, $offset);
	}

	public function uploadFile($filePath)
	{
		$rootPath = rtrim($this->activeDirectory, '/').'/'.FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME;
		$path = rtrim($rootPath, '/').'/'.basename($filePath);

		$fileSize = filesize($filePath);

		$this->delegate->willStartUpload();

		$fp = @fopen($filePath, 'rb');

		ftp_set_option($this->connectionId, FTP_AUTOSEEK, TRUE);

		$ret = ftp_nb_fput($this->connectionId, $path, $fp, FTP_BINARY, FTP_AUTORESUME);

		//get how many bytes were uploaded
		$this->offset = $this->state->getOffset();
		while ($ret == FTP_MOREDATA) {
			$ret = ftp_nb_continue($this->connectionId);

			$progress = ftell($fp)*100.0/$fileSize;
			$this->delegate->updateProgressManually($progress);
		}

		@fclose($fp);
		ftp_close($this->connectionId);

		if ($ret != FTP_FINISHED) {
			throw new Exception('The file was not uploaded correctly.');
		}
	}

	public function reload()
	{
		$this->delegate->reload();
	}

	public function deleteFile($fileName)
	{
		return @ftp_delete($this->connectionId, $fileName);
	}

	public function deleteFolder($folderName)
	{
		return $this->deleteFolderWithFiles($folderName);
	}

	private function deleteFolderWithFiles($directory)
	{
		if (empty($directory)) {
			return false;
		}

		if (!(@ftp_rmdir($this->connectionId, $directory) || @ftp_delete($this->connectionId, $directory))) {
			//if the attempt to delete fails, get the file listing
			$fileList = @ftp_nlist($this->connectionId, $directory);

			//loop through the file list and recursively delete the file in the list
			foreach ($fileList as $file) {
				if ($file=='.' || $file=='..') {
					continue;
				}

				$this->deleteFolderWithFiles($directory.'/'.$file);
			}

			//if the file list is empty, delete the directory we passed
			$this->deleteFolderWithFiles($directory);
		}

		return true;
	}
}
