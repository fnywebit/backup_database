<?php

if (!defined('ABSPATH')) {
	die();
}

$migrateTo = FNYBackupConfig::get("FNY_DATABASE_BACKUP_MIGRATE_TO");
?>

<div>
	<br>
	<div class="container">
		<div class="row">
			<div class="col-md-6">
				<div class="fny-migrate-alert"></div>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="row">
			<div class="col-md-6">
				<br>
				<div class="form-group">
					<label class="col-md-3 control-label" for="fny-migrate-from"><?php echo _e('Migrate from *')?></label>
					<div class="col-md-8">
						<input id="fny-migrate-from" name="fny-migrate-from" type="text" class="form-control input-md" value="<?php echo FNY_DATABASE_BACKUP_SITE_URL?>"  disabled>
					</div>
				</div>
				<br>
				<br>
				<div class="form-group">
					<label class="col-md-3 control-label" for="fny-migrate-to"><?php echo _e('Migrate to *')?></label>
					<div class="col-md-8">
						<input id="fny-migrate-to" name="fny-migrate-to" type="text" class="form-control input-md" value="<?php echo $migrateTo ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<br>
	<div class="container">
		<div class="row">
			<button class="btn btn-success" id="fny-save-migrate-settings">Save</button>
			<button class="btn btn-danger" id="fny-reset-migrate-settings" <?php echo $migrateTo?"":"disabled" ?>>Reset</button>
		</div>
	</div>
</div>
