<?php
if (!defined('ABSPATH')) {
	die();
}

$bucket = FNYBackupConfig::get('FNY_AMAZON_BUCKET');
$region = FNYBackupConfig::get('FNY_AMAZON_BUCKET_REGION');
$secretAccessKey = FNYBackupConfig::get('FNY_AMAZON_KEY');
$accessKey = FNYBackupConfig::get('FNY_AMAZON_SECRET_KEY');

$host = FNYBackupConfig::get('FNY_FTP_HOST');
$port = FNYBackupConfig::get('FNY_FTP_PORT');
$user = FNYBackupConfig::get('FNY_FTP_USER');
$passsword = FNYBackupConfig::get('FNY_FTP_PASSWORD');
$rootFolder = FNYBackupConfig::get('FNY_FTP_ROOT_FOLDER');
$isConnected = FNYBackupConfig::get('FNY_FTP_CONNECTED');

$googleConnectionString = FNYBackupConfig::get('FNY_DATABASE_BACKUP_GDRIVE_CONNECTION_STRING');
?>
<br>
<div>
	<div class="container">
		<div class="row">
			<div class="col-md-6">
				<div class="fny-cloud-alert"></div>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="row">
			<div class="col-md-6">
				<label class="fny-cloud-name-label">Amazon</label>
				<form class="form-horizontal" data-sgform="ajax" data-type="fnyStoreAmazonSettings">
					<div class="modal-body">
						<div class="col-md-12">

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-amazon-bucket"><?php echo _e('Bucket *')?></label>
								<div class="col-md-8">
									<input id="fny-amazon-bucket" name="fny-amazon-bucket" type="text" class="form-control input-md" value="<?php echo $bucket?>" <?php echo $bucket?"disabled":''?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-amazon-access-key"><?php echo _e('Access Key *')?></label>
								<div class="col-md-8">
									<input id="fny-amazon-access-key" name="fny-amazon-access-key" type="text" class="form-control input-md" value="<?php echo $accessKey?>" <?php echo $accessKey?"disabled":''?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-amazon-secret-access-key"><?php echo _e('Secret Access Key *')?></label>
								<div class="col-md-8">
									<input id="fny-amazon-secret-access-key" name="fny-amazon-secret-access-key" type="text" class="form-control input-md" value="<?php echo $secretAccessKey?>" <?php echo $secretAccessKey?"disabled":''?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-amazon-bucket-region"><?php echo _e('Region *')?></label>
								<div class="col-md-8">
									<select class="form-control input-md" id="fny-amazon-bucket-region" name="fny-amazon-bucket-region" <?php echo $region?"disabled":''?>>
										<option value="us-east-1" <?php echo ($region == "us-east-1")?"selected":''?>>US Standard</option>
										<option value="us-east-2" <?php echo ($region == "us-east-2")?"selected":''?>>Ohio</option>
										<option value="us-west-2" <?php echo ($region == "us-west-2")?"selected":''?>>Oregon</option>
										<option value="us-west-1" <?php echo ($region == "us-west-1")?"selected":''?>>Northern California</option>
										<option value="eu-west-1" <?php echo ($region == "eu-west-2")?"selected":''?>>Ireland</option>
										<option value="ap-southeast-1" <?php echo ($region == "ap-southeast-1")?"selected":''?>>Singapore</option>
										<option value="ap-northeast-1" <?php echo ($region == "ap-northeast-1")?"selected":''?>>Tokyo</option>
										<option value="ap-southeast-2" <?php echo ($region == "ap-southeast-2")?"selected":''?>>Sydney</option>
										<option value="sa-east-1" <?php echo ($region == "sa-east-1")?"selected":''?>>Sao Paulo</option>
										<option value="eu-central-1" <?php echo ($region == "eu-central-1")?"selected":''?>>Frankfurt</option>
										<option value="ap-northeast-2" <?php echo ($region == "ap-northeast-2")?"selected":''?>>Seoul</option>
									</select>
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" onclick="fnyDump.storeAmazonSettings()" <?php echo $secretAccessKey?'disabled':''?>><?php echo _e('Connect')?></button>
						<button type="button" class="btn btn-danger" onclick="fnyDump.disconnectAmazon()" <?php echo $secretAccessKey?'':'disabled'?>><?php echo _e('Disconnect')?></button>
					</div>
				</form>
			</div>
		</div>

		<div class="row">
			<div id="fny-ftp-form" class="col-md-6">
				<label class="fny-cloud-name-label">Google Drive</label>
				<form class="form-horizontal" data-sgform="ajax" data-type="storeAmazonSettings">
					<div class="modal-body">
						<div class="col-md-8">
							<label><?php echo $googleConnectionString ?></label>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" onclick="fnyDump.connectToGDrive()" <?php echo $googleConnectionString?"disabled":"" ?>><?php echo _e('Connect')?></button>
						<button type="button" class="btn btn-danger" onclick="fnyDump.disconnectGDrive()" <?php echo $googleConnectionString?"":"disabled" ?>><?php echo _e('Disconnect')?></button>
					</div>
				</form>
			</div>
		</div>

		<div class="row">
			<div id="fny-ftp-form" class="col-md-6">
				<label class="fny-cloud-name-label">FTP</label>
				<form class="form-horizontal" data-sgform="ajax" data-type="fnyStoreFtpSettings">
					<div class="modal-body">
						<div class="col-md-12">
							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-ftp-host"><?php echo _e('Host *')?></label>
								<div class="col-md-8">
									<input id="fny-ftp-host" name="fny-ftp-host" type="text" class="form-control input-md" value="<?php echo $host?$host:'' ?>" <?php echo $isConnected?"disabled":"" ?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-ftp-user"><?php echo _e('User *')?></label>
								<div class="col-md-8">
									<input id="fny-ftp-user" name="fny-ftp-user" type="text" class="form-control input-md" value="<?php echo $user?$user:'' ?>" <?php echo $isConnected?"disabled":"" ?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-ftp-pass"><?php echo _e('Password *')?></label>
								<div class="col-md-8">
									<input id="fny-ftp-pass" name="fny-ftp-pass" type="text" class="form-control input-md" value="<?php echo $passsword?$passsword:'' ?>" <?php echo $isConnected?"disabled":"" ?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-ftp-port"><?php echo _e('Port *')?></label>
								<div class="col-md-8">
									<input id="fny-ftp-port" name="fny-ftp-port" type="text" class="form-control input-md" value="<?php echo $port?$port:'21' ?>" <?php echo $isConnected?"disabled":"" ?>>
								</div>
							</div>

							<div class="form-group">
								<label class="col-md-3 control-label" for="fny-ftp-root"><?php echo _e('Root directory *')?></label>
								<div class="col-md-8">
									<input id="fny-ftp-root" name="fny-ftp-root" type="text" class="form-control input-md" value="<?php echo $rootFolder?$rootFolder:'/' ?>" <?php echo $isConnected?"disabled":"" ?>>
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>
					<div class="modal-footer">
						<button id="fny-storage-ftp" type="button" class="btn btn-primary" onclick="fnyDump.storeFtpSettings()" <?php echo $isConnected?"disabled":"" ?>><?php echo _e('Connect')?></button>
						<button type="button" class="btn btn-danger" onclick="fnyDump.disconnectFtp()" <?php echo $isConnected?"":"disabled" ?>><?php echo _e('Disconnect')?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
