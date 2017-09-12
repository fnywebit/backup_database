<?php

if (!defined('ABSPATH')) {
	die;
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');
require_once(FNY_DATABASE_BACKUP_STORAGE_PATH.'FNYAmazon.php');

if(count($_POST)) {
	$_POST = wp_unslash($_POST);
	$_POST = array_map('stripslashes', $_POST);

	if(isset($_POST['cancel'])) {
		FNYBackupConfig::set('FNY_AMAZON_BUCKET',false);
		FNYBackupConfig::set('FNY_AMAZON_KEY',false);
		FNYBackupConfig::set('FNY_AMAZON_SECRET_KEY',false);
		FNYBackupConfig::set('FNY_AMAZON_BUCKET_REGION', false);
		FNYBackupConfig::set('FNY_STORAGE_AMAZON_CONNECTED', false);

		die('success');
	}

	$options = $_POST;
	$error = array();
	$success = "success";

	if(!isset($options['fnyAmazonBucket'])) {
		array_push($error, 'Bucket field is required.');
	}
	if(!isset($options['fnyAmazonAccessKey'])) {
		array_push($error, 'Access key field is required.');
	}
	if(!isset($options['fnyAmazonSecretAccessKey'])) {
		array_push($error, 'Secret access key field is required.');
	}
	if(!isset($options['fnyAmazonRegion'])) {
		array_push($error, 'Bucket region field is required.');
	}

	//If there are errors do not continue
	if(count($error)) {
		die(json_encode($error));
	}

	//Try to connect
	try {
		FNYBackupConfig::set('FNY_AMAZON_BUCKET', $options['fnyAmazonBucket']);
		FNYBackupConfig::set('FNY_AMAZON_KEY', $options['fnyAmazonAccessKey']);
		FNYBackupConfig::set('FNY_AMAZON_SECRET_KEY', $options['fnyAmazonSecretAccessKey']);
		FNYBackupConfig::set('FNY_AMAZON_BUCKET_REGION', $options['fnyAmazonRegion']);

		$amazon = new FNYAmazon();
		if ($amazon->connect()) {
			FNYBackupConfig::set('FNY_STORAGE_AMAZON_CONNECTED', '1');
			die($success);
		}
		else {
			FNYBackupConfig::set('FNY_STORAGE_AMAZON_CONNECTED', '');
			array_push($error, 'Colud not connect to server. Please check given ditails');
			die(json_encode($error));
		}
	}
	catch(Exception $exception) {
		array_push($error, $exception->getMessage());
		die(json_encode($error));
	}
}
