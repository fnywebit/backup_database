<?php
if (!defined('ABSPATH')) {
	die();
}

require_once(FNY_DATABASE_BACKUP_CORE_PATH.'FNYBackupDump.php');
$backups = FNYBackupDump::getAllBackups();
$ajaxNonce = wp_create_nonce('fny-backup-ajax-nonce');
$activeBackupId = FNYBackupDump::getActiveBackupId();
$downloadUrl = admin_url('admin-post.php?action=fny_download_backup&');
?>
<div class="body">
	<div class="container-fluid">
		<input id="fny-backup-ajax-nonce" value="<?php echo $ajaxNonce ?>" hidden>
		<input id="fny-active-backup-id" value="<?php echo $activeBackupId['id'] ?>" hidden>
		<br>
		<ul class="nav nav-tabs">
			<li class="active"><a data-toggle="tab" href="#fny-db-backups">Backups</a></li>
			<li><a data-toggle="tab" href="#fny-db-settings">Settings</a></li>
			<li><a data-toggle="tab" href="#fny-db-clouds">Clouds</a></li>
			<li><a data-toggle="tab" href="#fny-db-schedule">Schedule</a></li>
			<?php if (FNY_DATABASE_BACKUP_MIGRATION_AVAILABLE):?>
				<li><a data-toggle="tab" href="#fny-db-migrate">Migrate</a></li>
			<?php endif;?>
		</ul>

		<div class="tab-content">
			<div id="fny-db-backups" class="tab-pane fade in active row">
				<?php require_once(FNY_DATABASE_BACKUP_PUBLIC_PATH.'pages/fnyDatabaseBackupBackupsPage.php') ?>
			</div>
			<div id="fny-db-settings" class="tab-pane fade">
				<?php require_once(FNY_DATABASE_BACKUP_PUBLIC_PATH.'pages/fnyDatabaseBackupSettingsPage.php') ?>
			</div>
			<div id="fny-db-clouds" class="tab-pane fade">
				<?php require_once(FNY_DATABASE_BACKUP_PUBLIC_PATH.'pages/fnyDatabaseBackupCloudsPage.php') ?>
			</div>
			<div id="fny-db-schedule" class="tab-pane fade">
				<?php require_once(FNY_DATABASE_BACKUP_PUBLIC_PATH.'pages/fnyDatabaseBackupSchedulePage.php') ?>
			</div>
			<?php if (FNY_DATABASE_BACKUP_MIGRATION_AVAILABLE):?>
				<div id="fny-db-migrate" class="tab-pane fade">
					<?php require_once(FNY_DATABASE_BACKUP_PUBLIC_PATH.'pages/fnyDatabaseBackupMigration.php') ?>
				</div>
			<?php endif;?>
		</div>
	</div>
</div>
