<?php

/*
* Plugin name: Backup Database Pro
* Plugin URI: http://fny-webit.com/backup-database
* Description: FNY Backup Database is the best choice for WordPress based WebSites.
* Author: FNY Web-IT
* Author URI: http://fny-webit.com
* Version: 1.6
* License: GPL-2.0+
* License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

require_once plugin_dir_path(__FILE__).'com/boot.php';

register_activation_hook(__FILE__, 'fny_install_database_backup');
register_uninstall_hook(__FILE__, 'fny_uninstall_database_backup');
add_action('init', 'fnyRegisterAjaxCalls');
add_action('fny_db_schedule_action', 'fny_db_schedule_action');

function fny_db_schedule_action()
{
	require_once(FNY_DATABASE_BACKUP_PUBLIC_PATH.'cron/startBackup.php');
}

function fny_install_database_backup()
{
	FNYBackupCore::install();
}

function fny_uninstall_database_backup()
{
	FNYBackupCore::uninstall();
}

add_action('admin_menu', 'fny_database_backup_menu');
function fny_database_backup_menu()
{
	add_menu_page("Database Backup", "DatabaseBackup", "manage_options", "fny_database_backup_admin", "fny_database_backup_admin_page", "dashicons-backup");
}

function fny_database_backup_admin_page()
{
	wp_enqueue_script('fny-bootstrap-js', plugin_dir_url(__FILE__).'public/bootstrap/js/bootstrap.min.js', array('jquery', 'jquery-effects-core', 'jquery-effects-transfer'), '1.0.0', true);

	echo '<script type="text/javascript">';
	echo 'function fnyDbGetAjaxUrl(url) {'.'if (url==="connect_to_dropbox" || url==="connect_to_gdrive") return "'.admin_url('admin-post.php?action=').'"+url;'.'return "'.admin_url('admin-ajax.php').'";}</script>';

	wp_enqueue_script('fny-database-backup-js', plugin_dir_url(__FILE__).'public/js/fnyDatabaseBackup.js');
	wp_enqueue_style('fny-bootstrap-css', plugin_dir_url(__FILE__).'public/bootstrap/css/bootstrap.min.css');
	wp_enqueue_style('fny-backupdatabase-css', plugin_dir_url(__FILE__).'public/css/fnyDatabaseBackup.css');

    require_once(plugin_dir_path(__FILE__).'/public/pages/fnyDatabaseBackupMainPage.php');
}

function fnyRegisterAjaxCalls()
{
	if (is_super_admin()) {
		// Ajax actions
		add_action('wp_ajax_start_backup', 'fnyStartBackup');
		add_action('wp_ajax_check_backup_creation', 'fnyCheckBackupCreation');
		add_action('wp_ajax_delete_backup', 'fnyDeleteBackup');
		add_action('wp_ajax_stop_backup', 'fnyStopBackup');
		add_action('wp_ajax_check_action_status', 'fnyCheckActionStatus');
		add_action('wp_ajax_save_settings', 'fnySaveSettings');
		add_action('wp_ajax_save_schedule_settings', 'fnySaveScheduleSettings');
		add_action('wp_ajax_connect_to_gdrive', 'fnyConnectToGdrive');
		add_action('wp_ajax_fny_db_store_amazon_settings', 'fnyDbStoreAmazonSettings');
		add_action('wp_ajax_fny_db_store_ftp_settings', 'fnyDbStoreFTPSettings');
		add_action('wp_ajax_save_migrate_settings', 'fnySaveMigrateSettings');

		add_action('admin_post_connect_to_gdrive', 'fnyConnectToGdrive');
		add_action('admin_post_fny_download_backup', 'fnyDownloadBackup');
	}
}

function fnySaveMigrateSettings()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'saveMigrateSettings.php');
}

function fnyDbStoreFTPSettings()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'fnyDbStoreFTPSettings.php');
}

function fnyDbStoreAmazonSettings()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'fnyDbStoreAmazonSettings.php');
}

function fnyConnectToGdrive()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'connectGDrive.php');
}

function fnySaveScheduleSettings()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'saveScheduleSettings.php');
}

function fnySaveSettings()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'saveSettings.php');
}

function fnyDownloadBackup()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'downloadBackup.php');
}

function fnyCheckActionStatus()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'checkActionStatus.php');
}

function fnyStopBackup()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'stopBackup.php');
}

function fnyDeleteBackup()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'deleteBackup.php');
}

function fnyCheckBackupCreation()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'checkBackupCreation.php');
}

function fnyStartBackup()
{
	require_once(FNY_DATABASE_BACKUP_AJAX_PATH.'startBackup.php');
}
