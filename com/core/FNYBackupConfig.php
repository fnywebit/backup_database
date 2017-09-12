<?php

class FNYBackupConfig
{
	private static $values = array();

	public static function set($key, $value, $forced = true)
	{
		self::$values[$key] = $value;

		if ($forced)
		{
			global $wpdb;
			$query = 'INSERT INTO '.$wpdb->prefix.'fny_configs (ckey, cvalue) VALUES (%s, %s) ON DUPLICATE KEY UPDATE cvalue = %s';

			$res = $wpdb->query($wpdb->prepare($query, array($key, $value, $value)));

			return $res;
		}

		return true;
	}

	public static function get($key, $forced = false)
	{
		if (!$forced) {
			if (isset(self::$values[$key])) {
				return self::$values[$key];
			}

			if (defined($key)) {
				return constant($key);
			}
		}

		global $wpdb;
		$data = array();
		$query = "SHOW TABLES LIKE '".$wpdb->prefix."fny_configs'";
		$res = $wpdb->get_results($query, ARRAY_A);

		if ($res) {
			$query = 'SELECT cvalue, NOW() FROM '.$wpdb->prefix.'fny_configs WHERE ckey = %s';
			$data = $wpdb->get_results($wpdb->prepare($query, array($key)), ARRAY_A);
		}

		if (!count($data)) {
			return null;
		}

		self::$values[$key] = $data[0]['cvalue'];
		return $data[0]['cvalue'];
	}

	public static function getAll()
	{
		global $wpdb;
		$configs = array();
		$query = "SHOW TABLES LIKE '".$wpdb->prefix."fny_configs'";
		$res = $wpdb->get_results($query, ARRAY_A);

		if ($res) {
			$query = 'SELECT * FROM '.$wpdb->prefix.'fny_configs';
			$res = $wpdb->get_results($query, ARRAY_A);

			if ($res) {
				foreach ($res as $config) {
					self::$values[$config['ckey']] = $config['cvalue'];
					$configs[$config['ckey']] = $config['cvalue'];
				}
			}
		}

		return $configs;
	}
}
