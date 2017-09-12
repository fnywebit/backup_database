<?php
if (!defined('ABSPATH')) {
	die();
}
?>

<table class="table fny-border">
	<?php if(FNY_DATABASE_BACKUP_EMAIL_AVAILABLE):?>
		<tr>
			<td><strong>Email Notification:</strong></td>
			<td><input type="text" id="fny-backup-email-notification" value="<?php echo FNYBackupConfig::get('FNY_DATABASE_BACKUP_EMAIL_NOTIFICATION')?esc_html(FNYBackupConfig::get('FNY_DATABASE_BACKUP_EMAIL_NOTIFICATION')):''?>"></td>
		</tr>
	<?php endif;?>
	<tr>
		<td><strong>Backup retention:</strong></td>
		<td><input id="fny-backup-retention" value="<?php echo FNYBackupConfig::get('FNY_DATABASE_BACKUP_RETENTION')?(int)FNYBackupConfig::get('FNY_DATABASE_BACKUP_RETENTION'):FNY_DATABASE_BACKUP_DEFAULF_RETENTION?>"></td>
	</tr>
	<tr>
		<td><strong>Backup file prefix:</strong></td>
		<td><input id="fny-backup-file-prefiix" value="<?php echo FNYBackupConfig::get('FNY_DATABASE_BACKUP_FILE_PREFIX')?esc_html(FNYBackupConfig::get('FNY_DATABASE_BACKUP_FILE_PREFIX')):FNY_DATABASE_BACKUP_DEFAULF_PREFIX?>"></td>
	</tr>
</table>
<button class="btn btn-danger" id="fny-save-settings">Save</button>
