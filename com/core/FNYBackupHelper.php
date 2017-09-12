<?php

class FNYBackupHelper
{
	public static function parseUrl($url)
	{
		$urlComponents = parse_url($url);
		$domain = $urlComponents['host'];
		$port = '';

		if (isset($urlComponents['port']) && strlen($urlComponents['port'])) {
			$port = ":".$urlComponents['port'];
		}

		$domain = preg_replace("/(www|\dww|w\dw|ww\d)\./", "", $domain);

		$path = "";
		if (isset($urlComponents['path'])) {
		    $path = $urlComponents['path'];
		}

		return $domain.$port.$path;
	}

	public static function deleteFolder($folder)
	{
		$dirHandle = null;
		if (is_dir($folder)) {
			$dirHandle = opendir($folder);
		}

		if (!$dirHandle) {
			return false;
		}

		while ($file = readdir($dirHandle)) {
			if ($file != "." && $file != "..") {
				if (!is_dir($folder."/".$file)) {
					@unlink($folder."/".$file);
				}
				else {
					self::deleteFolder($folder.'/'.$file);
				}
			}
		}

		closedir($dirHandle);
		return @rmdir($folder);
	}

	public static function downloadFile($path, $type)
	{
		if (file_exists($path)) {
			header('Content-Description: File Transfer');
			header('Content-Type: '.$type);
			header('Content-Disposition: attachment; filename="'.basename($path).'";');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($path));
			readfile($path);
		}

		exit;
	}

	public static function deleteOutdatedBackups()
	{
		$retention = FNYBackupConfig::get('FNY_DATABASE_BACKUP_RETENTION')?FNYBackupConfig::get('FNY_DATABASE_BACKUP_RETENTION'):FNY_DATABASE_BACKUP_DEFAULF_RETENTION;
		$backups = self::scanDir();

		while (count($backups) > $retention) {
			$backup = key($backups);
			array_shift($backups);
			self::deleteFolder(FNY_DATABASE_BACKUP_FOLDER_PATH.$backup);
		}
	}

	private static function scanDir()
	{
		$backupFolders = array();

		if (is_dir(FNY_DATABASE_BACKUP_FOLDER_PATH)) {
			$backups = scandir(FNY_DATABASE_BACKUP_FOLDER_PATH);

			foreach ($backups as $key => $backup) {
				if ($backup == "." || $backup == "..") {
					continue;
				}

				if (is_dir(FNY_DATABASE_BACKUP_FOLDER_PATH.$backup)) {
					$backupFolders[$backup] = filemtime(FNY_DATABASE_BACKUP_FOLDER_PATH.$backup);
				}
			}
		}

		// Sort(from low to high) backups by creation date
		asort($backupFolders);
		return $backupFolders;
	}

	public static function getPendingStorageUploads()
	{
		$storages = array();

		if (FNYBackupConfig::get('FNY_STORAGE_AMAZON_CONNECTED')) {
			$storages[] = FNY_DATABASE_BACKUP_AMAZON;
		}

		if (FNYBackupConfig::get('FNY_DATABASE_BACKUP_GDRIVE_CONNECTION_STRING')) {
			$storages[] = FNY_DATABASE_BACKUP_GOOGLE_DRIVE;
		}

		if (FNYBackupConfig::get('FNY_FTP_CONNECTED')) {
			$storages[] = FNY_DATABASE_BACKUP_FTP;
		}

		return $storages;
	}

	public static function getCurrentUrlScheme()
	{
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')?'https':'http';
	}
}
