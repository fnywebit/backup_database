<?php

abstract class FNYStorage
{
	protected $connected = false;
	protected $activeDirectory = '';
	protected $delegate = null;

	abstract public function init();
	abstract public function connect();
	abstract public function connectOffline();
	abstract public function checkConnected();
	abstract public function getListOfFiles();
	abstract public function createFolder($folderName);
	abstract public function downloadFile($filePath, $size);
	abstract public function uploadFile($filePath);
	abstract public function deleteFile($fileName);
	abstract public function deleteFolder($folderName);
	abstract public function fileExists($path);

	public function __construct()
	{
		@session_start();
		$this->init();
		$this->checkConnected();
	}

	public function setActiveDirectory($directory)
	{
		$this->activeDirectory = $directory;
	}

	public function getActiveDirectory()
	{
		return $this->activeDirectory;
	}

	public function isConnected()
	{
		return $this->connected;
	}

	public function setDelegate($delegate)
	{
		$this->delegate = $delegate;
	}
}
