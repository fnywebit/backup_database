<?php
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupHelper.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYStorage.php');

use Aws\S3\S3Client;

class FNYAmazon extends FNYStorage
{
	private $client = null;
	private $bucket = '';
	private $key = '';
	private $secret = '';
	private $region = '';
	private $parts = array();

	public function init()
	{
		require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYAmazonIncludes.php');
		//check if ftp extension is loaded

		$this->key = FNYBackupConfig::get('FNY_AMAZON_KEY');
		$this->secret = FNYBackupConfig::get('FNY_AMAZON_SECRET_KEY');
		$this->region = FNYBackupConfig::get('FNY_AMAZON_BUCKET_REGION');
		$this->bucket = FNYBackupConfig::get('FNY_AMAZON_BUCKET');

		$this->client = S3Client::factory(array(
			'signature' => 'v4',
			'version'   => 'latest',
			'region'    => $this->region,
			'key' 		=> $this->key,
			'secret' 	=> $this->secret
		));
	}

	public function connect()
	{
		try {
			// This will check if given credentials are valid or not. In case of not valid credentials it will throw an exception.
			$this->client->listBuckets();
			$this->connected = true;

			return true;
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function connectOffline()
	{
		$this->connect();
	}

	public function checkConnected()
	{
		$this->connected = $this->isConnected()?true:false;
	}

	public function getListOfFiles()
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$listOfFiles = array();
		$backupFolderName = FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME."/";

		$objects = $this->client->listObjects(array(
			'Bucket' => $this->bucket,
			"Prefix" => $backupFolderName
		));

		for($i=1; $i<count($objects['Contents']); $i++) {
			$size = $objects['Contents'][$i]['Size'];
			$date = $objects['Contents'][$i]['LastModified'];
			$name = basename($objects['Contents'][$i]['Key']);

			$listOfFiles[$name] = array(
				'name' => $name,
				'size' => $size,
				'date' => $date,
				'path' => $objects['Contents'][$i]['Key']
			);
		}

		return $listOfFiles;
	}

	public function createFolder($folderName)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$result = $this->client->putObject(array(
			'Bucket' => $this->bucket,
			'Key' => $folderName."/",
			'Body' => "",
			'ACL' => 'public-read'
		));

		return $result['ObjectURL'];
	}

	public function downloadFile($filePath, $fileSize)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$result = array();
		if ($filePath) {
			$chunk = 1.0*1024*1024;
			$start = 0;
			$end = $chunk;

			$fd = fopen(FNY_DATABASE_BACKUP_FOLDER_PATH.basename($filePath), "w");
			$result = array();
			$ret = false;

			while (true) {
				if (!file_exists(FNY_DATABASE_BACKUP_FOLDER_PATH.basename($filePath))) {
					$ret = false;
					break;
				}

				if ($start >= $fileSize) {
					$ret = true;
					break;
				}

				if ($end > $fileSize) {
					$end = $fileSize;
				}

				$result = $this->client->getObject(array(
					'Bucket' => $this->bucket,
					'Key' => $filePath,
					'Range' => "bytes=$start-$end",
				));
				$data = $result['Body'];

				if (strlen($data)) {
					fwrite($fd, $data);
				}

				$start = $end+1;
				$end += $chunk;
			}
		}

		fclose($fd);
		return $ret;
	}

	public function uploadFile($filePath)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$fileSize = filesize($filePath);
		$keyname = basename($filePath);
		$this->delegate->willStartUpload(1);

		$result = $this->client->createMultipartUpload(array(
			'Bucket' => $this->bucket,
			'Key'    => FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME.'/'.$keyname
		));
		$uploadId = $result['UploadId'];

		try {
			$file = fopen($filePath, 'r');

			$byteOffset = 0;
			fseek($file, $byteOffset);

			$partNumber = 1;
			$this->parts = array();

			while ($byteOffset < $fileSize) {
				$result = $this->client->uploadPart(array(
					'Bucket'     => $this->bucket,
					'Key'        => FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME.'/'.$keyname,
					'UploadId'   => $uploadId,
					'PartNumber' => $partNumber,
					'Body'       => fread($file, 5 * 1024 * 1024),
				));

				$this->parts[] = array(
					'ETag'       => $result['ETag'],
					'PartNumber' => $partNumber++
				);

				$byteOffset = ftell($file);
				$progress = $byteOffset*100.0/$fileSize;

				$this->delegate->updateProgressManually($progress);
			}

			fclose($file);

			$result = $this->client->completeMultipartUpload(array(
				'Bucket'   => $this->bucket,
				'Key'      => FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME.'/'.$keyname,
				'UploadId' => $uploadId,
				'Parts' => $this->parts
			));
		}
		catch (S3Exception $e) {
			$result = $this->client->abortMultipartUpload(array(
				'Bucket'   => $this->bucket,
				'Key'      => FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME.'/'.$keyname,
				'UploadId' => $uploadId
			));
		}
	}

	public function fileExists($path)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$filesList = $this->getListOfFiles();
		if (count($filesList)) {
			if (array_key_exists(basename($path), $filesList)) {
				return true;
			}
		}

		return false;
	}

	public function deleteFile($path)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$result = $this->client->deleteObject(array(
			'Bucket' => $this->bucket,
			'Key' 	 => $path
		));

		return $result;
	}

	public function deleteFolder($folderName)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$result = $this->client->deleteMatchingObjects($this->bucket, $folderName);

		return $result;
	}
}
