<?php
if (!defined('ABSPATH')) {
	die();
}
?>
<br>
<button class="btn btn-primary" id = "fny-backup-button">Backup</button>
<br>
<table class="table table-striped paginated">
	<thead>
		<tr>
			<th><input type="checkbox" id="fny-db-backup-select-all" autocomplete="off"></th>
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
				<td colspan="6"><?php echo 'No backups found.' ?></td>
			</tr>
		<?php endif;?>
		<?php foreach($backups as $backup): ?>
			<tr>
				<td>
					<input type="checkbox" autocomplete="off" value="<?php echo $backup['name']?>" <?php echo $backup['active']?'disabled':''?>>
				</td>
				<td><?php echo esc_html($backup['name']) ?></td>
				<td><?php echo !$backup['active']?esc_html($backup['size']):"" ?></td>
				<td>
					<div class="fny-progress-bar progress progress-striped active">
						<div id="fny-progress-<?php echo (int)$backup['id'] ?>" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
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

<button id="fny-db-backup-delete-multiple-rows" class="btn btn-danger" onclick="fnyDump.deleteMultiBackups()">Delete</button>
