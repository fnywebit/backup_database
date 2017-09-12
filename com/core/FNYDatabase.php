<?php

class FNYDatabase
{
	private static $instance = null;

	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = self::createAdapterInstance();
		}

		return self::$instance;
	}

	private static function createAdapterInstance()
	{
		$className = 'FNYDatabaseAdapter';
		require_once(FNY_DATABASE_BACKUP_CORE_PATH.$className.'.php');
		$adapter = new $className();
		return $adapter;
	}

	private function __construct()
	{

	}

	private function __clone()
	{

	}
}
