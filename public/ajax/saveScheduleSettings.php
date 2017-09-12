<?php

if (!defined('ABSPATH')) {
	die;
}

$retrieved_nonce = $_POST['_ajax_nonce'];
if (!wp_verify_nonce($retrieved_nonce, 'fny-backup-ajax-nonce')) {
	die('Failed security check');
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupConfig.php');

if (count($_POST)) {
	wp_clear_scheduled_hook("fny_db_schedule_action");

	if (isset($_POST['remove'])) {
		FNYBackupConfig::set("FNY_DATABASE_BACKUP_SCHEDULE_RECURRENCE", FNY_DATABASE_BACKUP_RECURRENCE_DAY);
		FNYBackupConfig::set("FNY_DATABASE_BACKUP_SCHEDULE_HOUR", '');
		FNYBackupConfig::set("FNY_DATABASE_BACKUP_SCHEDULE_DAY", '');
		wp_die();
	}

	$recurrence = (int)$_POST['recurrence'];
	$hour = (int)$_POST['hour'];
	$day = (int)$_POST['day'];

	FNYBackupConfig::set("FNY_DATABASE_BACKUP_SCHEDULE_RECURRENCE", $recurrence);
	FNYBackupConfig::set("FNY_DATABASE_BACKUP_SCHEDULE_HOUR", $hour);
	FNYBackupConfig::set("FNY_DATABASE_BACKUP_SCHEDULE_DAY", $day);

	$hour = sprintf("%02d:00", $hour);

	if ($recurrence == FNY_DATABASE_BACKUP_RECURRENCE_DAY) {
		$recurrence = "daily";
		$time = strtotime('Today '.$hour);
		if ($time < time()) {
			$time = strtotime('Next day '.$selectedTime);
		}
	}
	else if ($recurrence == FNY_DATABASE_BACKUP_RECURRENCE_WEEK) {
		switch ($day) {
			case 1:
				$day = 'Monday';
				break;
			case 2:
				$day = 'Tuesday';
				break;
			case 3:
				$day = 'Wednesday';
				break;
			case 4:
				$day = 'Thursday';
				break;
			case 5:
				$day = 'Friday';
				break;
			case 6:
				$day = 'Saturday';
				break;
			case 7:
				$day = 'Sunday';
				break;
		}

		$recurrence = "weekly";
		$time = strtotime('Next '.$day.' '.$hour);
	}
	else if ($recurrence == FNY_DATABASE_BACKUP_RECURRENCE_MONTH) {
		$recurrence = "monthly";
		$today = (int)date('d');

		if ($today < $day) {
			$time = strtotime('Today '.$hour);
			$time += ($day - $today)*86400;
		}
		else {
			$time = strtotime('first day of next month '.$hour);
			$time += ($day-1)*86400;
		}
	}

	wp_schedule_event($time, $recurrence, "fny_db_schedule_action");
}

wp_die();
