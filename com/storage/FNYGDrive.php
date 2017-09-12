<?php

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');
require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupHelper.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYStorage.php');
require_once(FNY_DATABASE_BACKUP_INCLUDS_PATH.'GoogleDrive/Google_Client.php');
require_once(FNY_DATABASE_BACKUP_INCLUDS_PATH.'GoogleDrive/contrib/Google_DriveService.php');
require_once(FNY_DATABASE_BACKUP_INCLUDS_PATH.'GoogleDrive/contrib/Google_Oauth2Service.php');
require_once(FNY_DATABASE_BACKUP_INCLUDS_PATH.'GoogleDrive/io/Google_HttpRequest.php');
require_once(FNY_DATABASE_BACKUP_INCLUDS_PATH.'GoogleDrive/io/Google_HttpStreamIO.php');

class FNYGDrive extends FNYStorage
{
	private $client = null;
	private $service = null;
	private $userInfoService = null;

	public function init()
	{
		$this->setActiveDirectory('root');
	}

	public function checkConnected()
	{
		$accessToken = $this->getAccessToken($expirationTs);
		$this->connected = ($accessToken&&$expirationTs>time())?true:false;

		if ($this->connected) {
			$client = $this->getClient();
			$client->setAccessToken($accessToken);
		}
	}

	public function connect()
	{
		if ($this->isConnected()) {
			return;
		}

		$authCode = $this->getAuthCodeFromURL($cancel);

		if ($cancel) {
			throw new Exception('User did not allow access');
		}

		$this->auth($authCode);
	}

	public function connectOffline()
	{
		if ($this->isConnected()) {
			return;
		}

		$refreshToken = $this->getRefreshToken();

		if (!$refreshToken) {
			throw new Exception('Refresh token not found');
		}

		$client = $this->getClient();

		//refresh access token using the refresh token
		$client->refreshToken($refreshToken);

		//set the new access token
		$accessToken = $client->getAccessToken();
		$client->setAccessToken($accessToken);
		$this->setAccessToken($accessToken, time()+3600);

		$this->connected = true;
	}

	private function getRefreshToken()
	{
		return FNYBackupConfig::get('FNY_DATABASE_BACKUP_GDRIVE_REFRESH_TOKEN');
	}

	private function auth($authCode = '')
	{
		$client = $this->getClient();
		$client->setApprovalPrompt('force');

		$redirectUri = FNY_DATABASE_BACKUP_GDRIVE_REDIRECT_URI;
		$client->setRedirectUri($redirectUri);

		$refUrl = $this->getRefURL();
		$client->setState(base64_encode($refUrl));

		$authUrl = $client->createAuthUrl();

		if ($authCode) {
			try {
				//exchange authorization code for access token
				$accessToken = $client->authenticate($authCode);
				$this->setAccessToken($accessToken, time()+3600);
				$client->setAccessToken($accessToken);
				$refreshToken = json_decode($accessToken, true);
				$refreshToken = $refreshToken['refresh_token'];

				//store refresh token
				FNYBackupConfig::set('FNY_DATABASE_BACKUP_GDRIVE_REFRESH_TOKEN', $refreshToken, true);

				$userInfo = $this->userInfoService->userinfo->get();

				if ($userInfo != null && $userInfo->getId() != null) {
					FNYBackupConfig::set('FNY_DATABASE_BACKUP_GDRIVE_CONNECTION_STRING', $userInfo->getEmail());
				}

				$this->connected = true;
				return;
			}
			catch (Exception $ex) {

			}
		}

		header('Location: '.$authUrl);
		exit;
	}

	private function getRefURL()
	{
		$refUrl = $this->getCurrentURL();
		if (!$_SERVER['QUERY_STRING']) {
			$refUrl .= '?code=';
		}
		else {
			$refUrl .= '&code=';
		}

		return $refUrl;
	}

	private function getService()
	{
		return $this->service;
	}

	private function getClient()
	{
		if (!$this->client) {
			$client = new Google_Client();
			$client->setUseObjects(true);
			$client->setClientId(FNY_DATABASE_BACKUP_GDRIVE_CLIENT_ID);
			$client->setClientSecret(FNY_DATABASE_BACKUP_GDRIVE_SECRET);
			$client->setScopes(array('https://www.googleapis.com/auth/drive', 'email'));
			$client->setAccessType('offline');
			$client->setApprovalPrompt('auto');
			$this->client = $client;

			$this->service = new Google_DriveService($client);

			$this->userInfoService = new Google_Oauth2Service($client);
		}

		return $this->client;
	}

	private function getAccessToken(&$expirationTs = 0)
	{
		$expirationTs = (int)@$_SESSION['fny_database_backup_gdrive_expiration_ts'];
		return @$_SESSION['fny_database_backup_gdrive_access_token'];
	}

	private function setAccessToken($accessToken, $expirationTs)
	{
		$_SESSION['fny_database_backup_gdrive_access_token'] = $accessToken;
		$_SESSION['fny_database_backup_gdrive_expiration_ts'] = $expirationTs;
	}

	public function getListOfFiles()
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$refreshToken = $this->getRefreshToken();
		$this->client->refreshToken($refreshToken);

		$subDirs = $this->getSubFolders(FNY_DATABASE_BACKUP_CLOUD_FOLDER_NAME);

		foreach ($subDirs as $folder) {
			$folderId = $this->getFileId($folder, true);
			$this->setActiveDirectory($folderId);
		}

		$listOfFiles = array();
		$parameters = array();
		$parameters['q'] = "'".$folderId."' in parents and trashed = false";
		$return = $this->search($parameters);

		for($i=0; $i<count($return); $i++){
			$size = $return[$i]->getFileSize();
			$date = $return[$i]->getCreatedDate();
			$name = $return[$i]->getTitle();

			$listOfFiles[$name] = array(
				'name' => $name,
				'size' => $size,
				'date' => $date,
				'path' => $return[$i]->getId()
			);
		}
		krsort($listOfFiles);
		return $listOfFiles;
	}

	private function search($parameters)
	{
		$result = array();
		$pageToken = NULL;

		$service = $this->getService();

		do {
			try {
				if ($pageToken) {
					$parameters['pageToken'] = $pageToken;
				}
				else {
					unset($parameters['pageToken']);
				}

				$files = $service->files->listFiles($parameters);
				$result = array_merge($result, $files->getItems());

				$pageToken = $files->getNextPageToken();
			}
			catch (Exception $e) {
				$pageToken = NULL;
			}
		}
		while ($pageToken);

		return $result;
	}

	private function searchFile($path)
	{
		$path = trim($path, '/');
		$folderTree = $this->getSubFolders($path);

		$id = '';
		foreach ($folderTree as $folder) {
			$isFolder = true;
			if ($folder == end($folderTree)) {
				$isFolder = false;
			}

			$id = $this->getFileId($folder, $isFolder);
			if ($id) {
				$this->setActiveDirectory($id);
			}
			else {
				return false;
			}
		}

		return $id;
	}

	public function fileExists($path)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$id = $this->searchFile($path);
		if ($id) {
			return true;
		}

		return false;
	}

	private function getFileId($fileName, $isFolder = false)
	{
		$parameters = array();
		$parameters['q'] = "'".$this->getActiveDirectory()."' in parents";

		if ($isFolder) {
			$parameters['q'] .= " and mimeType = 'application/vnd.google-apps.folder'";
		}

		$parameters['q'] .= " and title = '$fileName'";
		$parameters['q'] .= " and trashed = false";
		$res = $this->search($parameters);

		if (count($res)) {
			return $res[0]->id;
		}

		return false;
	}

	private function getSubFolders($path)
	{
		return explode('/', $path);
	}

	public function createFolder($folderName)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$folderTree = $this->getSubFolders($folderName);

		foreach ($folderTree as $folder) {
			$folderId = $this->getFileId($folder, true);
			if ($folderId) {
				$this->setActiveDirectory($folderId);
				continue;
			}

			$file = new Google_DriveFile();
			$file->setTitle($folder);
			$file->setMimeType('application/vnd.google-apps.folder');

			$parent = new Google_ParentReference();
			$parent->setId($this->getActiveDirectory());
			$file->setParents(array($parent));

			$createdFile = $this->service->files->insert($file, array(
				'mimeType' => 'application/vnd.google-apps.folder',
			));

			$this->setActiveDirectory($createdFile->id);
		}

		return $this->getActiveDirectory();
	}

	public function deleteFile($path)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$id = $this->searchFile($path);
		if ($id) {
			$service = $this->getService();
			$service->files->delete($id);
		}
	}

	public function deleteFolder($folderName)
	{
		return $this->deleteFile($folderName);
	}

	public function downloadFile($fileId, $size)
	{
		$this->connectOffline();
		if (!$this->isConnected()) {
			throw new Exception('Permission denied. Authentication required.');
		}

		$content = $this->service->files->get($fileId);
		$fileSize = $content->getFileSize();
		$downloadUrl = $content->getDownloadUrl();

		$fd = fopen(FNY_DATABASE_BACKUP_FOLDER_PATH.$content->getOriginalFilename(), "w");
		$ret = false;

		if ($downloadUrl) {
			$chunk = 1.0*1024*1024;
			$start = 0;
			$end = $chunk;

			$request = new Google_HttpRequest($downloadUrl, 'GET', null, null);
			$stream = new Google_HttpStreamIO();

			while (true) {
				$fExists = file_exists(FNY_DATABASE_BACKUP_FOLDER_PATH.$content->getOriginalFilename());
				if (!$fExists) {
					break;
				}

				if ($start >= $fileSize) {
					$ret = true;
					break;
				}

				if ($end > $fileSize) {
					$end = $fileSize;
				}

				$request->setRequestHeaders(array('Range' => "bytes=$start-$end"));
				$httpRequest = $stream->authenticatedRequest($request);

				if (!$httpRequest->getResponseBody()) {
					$ret = true;
					break;
				}

				fwrite($fd, $httpRequest->getResponseBody());

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

		if (!file_exists($filePath) || !is_readable($filePath)) {
			throw new Exception('File does not exist or is not readable: '.$filePath);
		}

		$chunkSizeBytes = 1024 * 1024;

		$mime = 'application/octet-stream';

		$file = new Google_DriveFile();
		$file->setTitle(basename($filePath));
		$file->setMimeType($mime);

		$parent = new Google_ParentReference();
		$parent->setId($this->getActiveDirectory());
		$file->setParents(array($parent));

		$fileSize = filesize($filePath);

		$media = new Google_MediaFileUpload($mime, null, true, $chunkSizeBytes);
		$media->setFileSize($fileSize);

		$this->client->setUseObjects(false);
		$result = $this->service->files->insert($file, array('mediaUpload' => $media));

		$status = false;
		$handle = fopen($filePath, "rb");

		$this->delegate->willStartUpload((int)ceil($fileSize/$chunkSizeBytes));

		$byteOffset = 0;
		fseek($handle, $byteOffset);

		while (!$status && $byteOffset < $fileSize) {
			$chunk = fread($handle, $chunkSizeBytes);
			$status = $media->nextChunk($result, $chunk);
			$byteOffset += strlen($chunk);

			if (!$this->delegate->shouldUploadNextChunk()) {
				break;
			}
		}

		fclose($handle);
	}

	private function getCurrentURL()
	{
		$http = FNYBackupHelper::getCurrentUrlScheme();
		$url = $http.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		return $url;
	}

	private function getAuthCodeFromURL(&$cancel = false)
	{
		$query = $_SERVER['QUERY_STRING'];
		if (!$query) {
			return '';
		}

		$query = explode('&', $query);
		$code = '';
		foreach ($query as $q) {
			$q = explode('=', $q);
			if ($q[0]=='code') {
				$code = $q[1];
			}
			else if ($q[0]=='cancel' && $q[1]=='1') {
				$cancel = true;
				break;
			}
		}

		return $code;
	}
}
