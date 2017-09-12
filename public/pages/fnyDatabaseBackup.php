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
<div class="container-fluid">
	<input id="fny-backup-ajax-nonce" value="<?php echo $ajaxNonce ?>" hidden>
	<input id="fny-active-backup-id" value="<?php echo $activeBackupId['id'] ?>" hidden>
	<br>
	<ul class="nav nav-tabs">
		<li class="active"><a data-toggle="tab" href="#Backup">Backup</a></li>
		<li><a data-toggle="tab" href="#Settings">Settings</a></li>
		<li><a data-toggle="tab" href="#Products">Products</a></li>
	</ul>

	<div class="tab-content">
		<div id="Backup" class="tab-pane fade in active row">
			<br>
			<button class="btn btn-primary" id = "fny-backup-button">Backup</button>
			<br>
			<table class="table table-striped paginated sg-backup-table">
				<thead>
					<tr>
						<th><?php echo 'Name' ?></th>
						<th><?php echo 'Size' ?></th>
						<th><?php echo 'Progress' ?></th>
						<th><?php echo 'Status' ?></th>
						<th><?php echo 'Options' ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(empty($backups)):?>
						<tr>
							<td colspan="5"><?php echo 'No backups found.' ?></td>
						</tr>
					<?php endif;?>
					<?php foreach($backups as $backup): ?>
						<tr>
							<td><?php echo esc_html($backup['name']) ?></td>
							<td><?php echo !$backup['active']?esc_html($backup['size']):"" ?></td>
							<td>
								<div class="fny-progress-bar progress progress-striped active">
									<div id="fny-progress-<?php echo (int)$backup['id'] ?>" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
										<span class="sr-only">40% Complete (success)</span>
									</div>
								</div>
							</td>
							<td>
								<?php if (!$backup['active']):?>
									<?php if ($backup['status'] == FNY_DATABASE_BACKUP_FINISHED_WITH_ERROR): ?>
										<span class="glyphicon glyphicon-warning-sign btn-xs text-warning" data-toggle="tooltip" data-placement="top" data-original-title="Error"></span>
									<?php else: ?>
										<span class="glyphicon glyphicon-ok btn-xs text-success"></span>
									<?php endif; ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if($backup['status'] == FNY_DATABASE_BACKUP_INPROGRESS): ?>
									<a class="fny-stop-backup btn btn-danger btn-xs" fny-data = "<?php echo (int)$backup['id']?>" href="javascript:void(0)" title="<?php echo 'Stop'?>">&nbsp;<i class="glyphicon glyphicon-stop" aria-hidden="true"></i>&nbsp;</a>
								<?php endif; ?>
								<?php if($backup['status'] != FNY_DATABASE_BACKUP_INPROGRESS): ?>
									<a href="javascript:void(0)" fny-data = "<?php echo esc_html($backup['name']);?>" class="btn btn-danger btn-xs fny-delete-backup" title="<?php echo 'Delete'?>">&nbsp;<i class="glyphicon glyphicon-remove" aria-hidden="true"></i>&nbsp;</a>
									<div class="btn-group">
										<a href="javascript:void(0)" class="fny-download-backup btn btn-primary dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false" title="<?php echo 'Download'?>">&nbsp;<i class="glyphicon glyphicon-download-alt" aria-hidden="true"></i>&nbsp;<span class="caret"></span></a>
										<ul class="dropdown-menu">
											<?php if($backup['backup']):?>
												<li>
													<a href="<?php echo $downloadUrl.'name='.esc_html(@$backup['name']).'&type=backup'?>">
														<i class="glyphicon glyphicon-hdd" aria-hidden="true"></i> <?php echo ' Backup'?>
													</a>
												</li>
											<?php endif;?>
											<?php if($backup['log']):?>
												<li>
													<a href="<?php echo $downloadUrl.'name='.esc_html(@$backup['name']).'&type=log'?>">
														<i class="glyphicon glyphicon-list-alt" aria-hidden="true"></i><?php echo ' Log' ?>
													</a>
												</li>
											<?php endif;?>
										</ul>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div id="Settings" class="tab-pane fade">
			<table class="table fny-border">
				<tr>
					<td><strong>Backup retention:</strong></td>
					<td><input id = "fny-backup-retention" value="<?php echo FNYBackupConfig::get('FNY_DATABASE_BACKUP_RETENTION')?(int)FNYBackupConfig::get('FNY_DATABASE_BACKUP_RETENTION'):FNY_DATABASE_BACKUP_DEFAULF_RETENTION?>"></td>
				</tr>
				<tr>
					<td><strong>Backup file prefix:</strong></td>
					<td><input id = "fny-backup-file-prefiix" value="<?php echo FNYBackupConfig::get('FNY_DATABASE_BACKUP_FILE_PREFIX')?esc_html(FNYBackupConfig::get('FNY_DATABASE_BACKUP_FILE_PREFIX')):FNY_DATABASE_BACKUP_DEFAULF_PREFIX?>"></td>
				</tr>
			</table>
			<button class="btn btn-danger" id="fny-save-settings">Save</button>
		</div>
		<div id="Products" class="tab-pane fade">

        <div class="container-fluid">
            <div class="row content" >
                <div class="col-xs-5 col-sm-5 col-md-5 col-lg-5 fny-btwn" style="margin:20px;">
                   <div class="row content"  style="background-color:#f9f9f9;">
                        <img class=" size-thumbnail alignleft" src="<?php echo FNY_DATABASE_BACKUP_IMAGES_URL.'share.png' ?>" style="background-color:#f9f9f9; height:150px; width:150px;" />
                        <h1 style="text-align: left;"><span style="color: #2175c8;" ><a href="https://wordpress.org/plugins/fny-social-media-share-buttons/" target="_blank">Social Media Share Buttons</a></span></h1>
                        <h5 style="text-align: left;"><span style="color: #18333f;"><strong> The ‘Social Media Share Buttons’ is a WordPress sharing plugin that helps people to share their posts and pages to any service. </strong></span></h5>
                        <a class="btn btn-primary" href="https://wordpress.org/plugins/fny-social-media-share-buttons/" target="_blank">Check Out Now!</a>
                    </div>
                    <div class="row content" style="background-color:#f9f9f9; margin-top: 5px; height: 25px;">
                        <div style="padding: 3px;">
                        <span style="margin-left:15px;"><i class="dashicons dashicons-admin-users"></i> <a href="http://fny-webit.com/" target="_blank">FNY Web-IT</a></span>
                        <span style="margin-left:50px;"><i class="dashicons dashicons-wordpress"></i> Tested with 4.7.4</span>
                        </div>
                    </div>
                </div>

                <div class="col-xs-5 col-sm-5 col-md-5 col-lg-5 fny-btwn" style="margin:20px;">
                   <div class="row content"  style="background-color:#f9f9f9;">
                        <img class=" size-thumbnail alignleft" src="<?php echo FNY_DATABASE_BACKUP_IMAGES_URL.'gallery.png' ?>" style="background-color:#f9f9f9; height:150px; width:150px;" />
                        <h1 style="text-align: left;"><span style="color: #2175c8;" ><a href="http://fny-webit.com/wordpress-plugins/" target="_blank">Photo Gallery</a></span></h1>
                        <h5 style="text-align: left;"><span style="color: #18333f;"><strong> Coming soon !</strong></span></h5>
                    </div>
                    <div class="row content" style="background-color:#f9f9f9; margin-top: 5px; height: 25px;">
                        <div style="padding: 3px;">
                        <span style="margin-left:15px;"><i class="dashicons dashicons-admin-users"></i> <a href="http://fny-webit.com/" target="_blank">FNY Web-IT</a></span>
                        <span style="margin-left:50px;"><i class="dashicons dashicons-wordpress"></i> Tested with 4.7.4</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		</div>
	</div>
</div>
