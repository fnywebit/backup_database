fnyDump = {};
FNY_DATABASE_BACKUP_RECURRENCE_DAY = 1;
FNY_DATABASE_BACKUP_RECURRENCE_WEEK = 2;
FNY_DATABASE_BACKUP_RECURRENCE_MONTH = 3;

FNY_DATABASE_BACKUP_AMAZON = 1;
FNY_DATABASE_BACKUP_GDRIVE = 2;
FNY_DATABASE_BACKUP_FTP = 3;

FNY_DATABASE_BACKUP_TYPE_DUMP = 1;
FNY_DATABASE_BACKUP_TYPE_UPLOAD = 2;

jQuery(document).ready( function() {
	if (jQuery('#fny-active-backup-id').val()) {
		fnyDump.getActionProgress();
	}

	jQuery('#fny-db-backup-delete-multiple-rows').hide();

	jQuery('#fny-db-backup-select-all').on('change', function(){
		var checkAll = jQuery('#fny-db-backup-select-all');
		jQuery('tbody input[type="checkbox"]:not(:disabled):visible').prop('checked', checkAll.prop('checked'));
		fnyDump.toggleMultiDeleteButton();
	});

    jQuery('tbody input[type="checkbox"]').on('change', function(){
        var numberOfBackups = jQuery('tbody input[type="checkbox"]').length;
        var numberOfChoosenBackups = fnyDump.getSelectedBackupsNumber();
        var isCheked = jQuery(this).is(':checked');
        fnyDump.toggleMultiDeleteButton();

        if(!isCheked) {
            jQuery('#fny-db-backup-select-all').prop('checked', false);
        }
        else {
            if (numberOfBackups == numberOfChoosenBackups) {
                jQuery('#fny-db-backup-select-all').prop('checked', true);
            }
        }
    });
});

fnyDump.deleteMultiBackups = function() {
	if (confirm("Are you sure?")) {
		var backups = jQuery('tbody input[type="checkbox"]:checked');
		var backupNames = [];
		backups.each(function(i) {
			backupNames[i] = jQuery(this).val();
		});

		var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
		var data = {
			_ajax_nonce: csrf_token,
			action: 'delete_backup',
			name: backupNames
		}

		jQuery.post(ajaxurl, data, function(response) {
			location.reload();
		});
	}
	else {
		return false;
	}
}

fnyDump.toggleMultiDeleteButton = function() {
	var numberOfChoosenBackups = fnyDump.getSelectedBackupsNumber();
	var target = jQuery('#fny-db-backup-delete-multiple-rows');

	if (numberOfChoosenBackups > 0) {
		target.show();
	}
	else {
		target.hide();
	}
}

fnyDump.getSelectedBackupsNumber = function() {
	return jQuery('tbody input[type="checkbox"]:checked').length
}

jQuery("#fny-db-schedule-recurrence").on('change', function() {
	var recurrence = jQuery(this).val();

	if (recurrence == FNY_DATABASE_BACKUP_RECURRENCE_DAY) {
		jQuery('#fny-db-schedule-recurrence-hour-div').show();
		jQuery('#fny-db-schedule-recurrence-day-of-week-div').hide();
		jQuery('#fny-db-schedule-recurrence-day-of-month-div').hide();
	}
	else if(recurrence == FNY_DATABASE_BACKUP_RECURRENCE_WEEK) {
		jQuery('#fny-db-schedule-recurrence-hour-div').hide();
		jQuery('#fny-db-schedule-recurrence-day-of-week-div').show();
		jQuery('#fny-db-schedule-recurrence-day-of-month-div').hide();
	}
	else if(recurrence == FNY_DATABASE_BACKUP_RECURRENCE_MONTH) {
		jQuery('#fny-db-schedule-recurrence-hour-div').hide();
		jQuery('#fny-db-schedule-recurrence-day-of-week-div').hide();
		jQuery('#fny-db-schedule-recurrence-day-of-month-div').show();
	}
});

jQuery("#fny-backup-button").on('click', function() {

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'start_backup'
	}

	fnyDump.checkBackupCreation();

	jQuery.post(ajaxurl, data, function(response) {
	});
});

jQuery("#fny-save-migrate-settings").on('click', function(e) {
	e.preventDefault();
	var data = {
		action: 'save_migrate_settings',
		migrateTo: jQuery("#fny-migrate-to").val()
	}

	if (jQuery("#fny-migrate-to").val() == '') {
		var alert = fnyDump.alertGenerator("Cannot migrate to empty url", 'alert-danger');
		jQuery('.fny-migrate-alert').prepend(alert);

		fnyDump.scrollToElement(".fny-migrate-alert");
		return;
	}

	jQuery.post(ajaxurl, data, function(response) {
		if (response != 0) {
			var alert = fnyDump.alertGenerator(response, 'alert-danger');
			jQuery('.fny-migrate-alert').prepend(alert);

			fnyDump.scrollToElement(".fny-migrate-alert");
		}
		else {
			location.reload();
		}
	});
});

jQuery("#fny-reset-migrate-settings").on('click', function() {
	var data = {
		action: 'save_migrate_settings',
		reset: '1'
	}

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});
});

jQuery("#fny-save-settings").on('click', function() {

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'save_settings',
		retention: jQuery("#fny-backup-retention").val(),
		prefix: jQuery("#fny-backup-file-prefiix").val()
	}

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});
});

jQuery('#fny-db-remove-schedule').on('click', function() {
	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'save_schedule_settings',
		remove: '1'
	}

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});
});

jQuery('.fny-delete-backup').on('click', function () {

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'delete_backup',
		name: jQuery(this).attr('fny-data')
	}

	if (confirm("Are you sure?")) {
		jQuery.post(ajaxurl, data, function(response) {
			location.reload();
		});
	}
	else {
		return false;
	}
});

jQuery('.fny-stop-backup').on('click', function () {

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'stop_backup',
		id: jQuery(this).attr('fny-data')
	}

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});
});

jQuery("#fny-db-save-schedule-settings").on('click', function() {
	var hour = 0;
	var day = '';
	var recurrence = jQuery('#fny-db-schedule-recurrence').val();

	if (recurrence == FNY_DATABASE_BACKUP_RECURRENCE_DAY) {
		hour = jQuery('#fny-db-schedule-recurrence-hour').val();
	}
	else if (recurrence == FNY_DATABASE_BACKUP_RECURRENCE_WEEK) {
		day = jQuery('#fny-db-schedule-recurrence-day-of-week').val();
	}
	else if (recurrence == FNY_DATABASE_BACKUP_RECURRENCE_MONTH) {
		day = jQuery('#fny-db-schedule-recurrence-day-of-month').val();
	}

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'save_schedule_settings',
		recurrence: jQuery('#fny-db-schedule-recurrence').val(),
		hour: hour,
		day: day
	}

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});
});

fnyDump.alertGenerator = function(content, alertClass){
	var alert = '';
	alert += '<div class="alert alert-dismissible '+alertClass+'">';
	alert += '<button type="button" class="close" data-dismiss="alert">×</button>';
	if(jQuery.isArray(content)){
		jQuery.each(content, function(index, value) {
			alert += value + '<br/>';
		});
	}
	else if(content != ''){
		alert += content.replace('[','').replace(']','').replace('"','');
	}
	alert += '</div>';
	return alert;
};

fnyDump.scrollToElement = function(id) {
	if (jQuery(id).position()) {
		if (jQuery(id).position().top < jQuery(window).scrollTop()) {
			//scroll up
			jQuery('html, body').animate({scrollTop:jQuery(id).position().top}, 1000);
		}
		else if (jQuery(id).position().top + jQuery(id).height() > jQuery(window).scrollTop() + (window.innerHeight || document.documentElement.clientHeight)) {
			//scroll down
			jQuery('html, body').animate({scrollTop:jQuery(id).position().top - (window.innerHeight || document.documentElement.clientHeight) + jQuery(id).height() + 15}, 1000);
		}
	}
};

fnyDump.disconnectFtp = function () {
	var data = {
		action: "fny_db_store_ftp_settings",
		cancel: 1
	}

	jQuery.post(ajaxurl, data, function(response) {
		jQuery('.alert').remove();

		if (typeof response !== 'undefined') {
			location.reload();
		}
		else {
			//if error
			var alert = fnyDump.alertGenerator(response, 'alert-danger');
			jQuery('.fny-cloud-alert').prepend(alert);

			//Before Ajax call
			jQuery('form[data-type=fnyStoreFtpSettings] .btn-primary').removeAttr('disabled');
			jQuery('form[data-type=fnyStoreFtpSettings] .btn-primary').html('Connect');

			fnyDump.scrollToElement(".fny-cloud-alert");
		}
	});
}

fnyDump.storeFtpSettings = function() {
	var error = [];
	// Validation
	jQuery('.alert').remove();
	var ftpForm = jQuery('form[data-type=fnyStoreFtpSettings]');
	ftpForm.find('input').each(function(){
		if (jQuery(this).val() <= 0) {
			var errorTxt = jQuery(this).closest('div').parent().find('label').html().slice(0,-2);
			error.push(errorTxt+' field is required.');
		}
	});

	//If any error show it and abort ajax
	if (error.length) {
		var alert = fnyDump.alertGenerator(error, 'alert-danger');
		jQuery('.fny-cloud-alert').prepend(alert);
		fnyDump.scrollToElement(".fny-cloud-alert");
		return false;
	}

	//Before Ajax call
	jQuery('form[data-type=fnyStoreFtpSettings] .btn-primary').attr('disabled', 'disabled');
	jQuery('form[data-type=fnyStoreFtpSettings] .btn-primary').html('Connecting...');

	//User credentials
	var data = {
		action: 'fny_db_store_ftp_settings',
		fnyFtpHost: jQuery('#fny-ftp-host').val(),
		fnyFtpPassword: jQuery('#fny-ftp-pass').val(),
		fnyFtpUser: jQuery('#fny-ftp-user').val(),
		fnyFtpPort: jQuery('#fny-ftp-port').val(),
		fnyRootFolder: jQuery("#fny-ftp-root").val(),
		fnyFtpString: jQuery('#fny-ftp-user').val()+'@'+jQuery('#fny-ftp-host').val()+':'+jQuery('#fny-ftp-port').val()
	}

	//On Success
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('.alert').remove();
		if(typeof response !== 'undefined') {
			location.reload();
		}
		else {
			//if error
			var alert = fnyDump.alertGenerator(response, 'alert-danger');
			jQuery('.fny-cloud-alert').prepend(alert);

			//Before Ajax call
			jQuery('form[data-type=fnyStoreFtpSettings] .btn-primary').removeAttr('disabled');
			jQuery('form[data-type=fnyStoreFtpSettings] .btn-primary').html('Connect');

			fnyDump.scrollToElement(".fny-cloud-alert");
		}
	});
}

fnyDump.disconnectAmazon = function () {
	var data = {
		action: "fny_db_store_amazon_settings",
		cancel: 1
	}

	jQuery.post(ajaxurl, data, function(response) {
		jQuery('.alert').remove();

		if(typeof response !== 'undefined') {
			location.reload();
		}
		else {
			//if error
			var alert = fnyDump.alertGenerator(response, 'alert-danger');
			jQuery('.fny-cloud-alert').prepend(alert);

			//Before Ajax call
			jQuery('form[data-type=fnyStoreAmazonSettings] .btn-primary').removeAttr('disabled');
			jQuery('form[data-type=fnyStoreAmazonSettings] .btn-primary').html('Connect');

			fnyDump.scrollToElement(".fny-cloud-alert");
		}
	});
}

fnyDump.storeAmazonSettings = function() {
	var error = [];
	// Validation
	jQuery('.alert').remove();
	var amazonForm = jQuery('form[data-type=fnyStoreAmazonSettings]');
	amazonForm.find('input').each(function() {
		if(jQuery(this).val() <= 0){
			var errorTxt = jQuery(this).closest('div').parent().find('label').html().slice(0,-2);
			error.push(errorTxt+' field is required.');
		}
	});

	// If any error show it and abort ajax
	if(error.length){
		var alert = fnyDump.alertGenerator(error, 'alert-danger');
		jQuery('.fny-cloud-alert').prepend(alert);
		fnyDump.scrollToElement(".fny-cloud-alert");
		return false;
	}

	jQuery('form[data-type=fnyStoreAmazonSettings] .btn-primary').attr('disabled', 'disabled');
	jQuery('form[data-type=fnyStoreAmazonSettings] .btn-primary').html('Connecting...');

	// User credentials
	var data = {
		action: "fny_db_store_amazon_settings",
		fnyAmazonBucket: jQuery('#fny-amazon-bucket').val(),
		fnyAmazonAccessKey: jQuery('#fny-amazon-access-key').val(),
		fnyAmazonSecretAccessKey: jQuery('#fny-amazon-secret-access-key').val(),
		fnyAmazonRegion: jQuery('#fny-amazon-bucket-region').val()
	}

	jQuery.post(ajaxurl, data, function(response) {
		jQuery('.alert').remove();

		if(typeof response !== 'undefined') {
			location.reload();
		}
		else {
			//if error
			var alert = fnyDump.alertGenerator(response, 'alert-danger');
			jQuery('.fny-cloud-alert').prepend(alert);

			//Before Ajax call
			jQuery('form[data-type=fnyStoreAmazonSettings] .btn-primary').removeAttr('disabled');
			jQuery('form[data-type=fnyStoreAmazonSettings] .btn-primary').html('Connect');

			fnyDump.scrollToElement(".fny-cloud-alert");
		}
	});
}

fnyDump.disconnectGDrive = function() {
	var data = {
		action: "connect_to_gdrive",
		cancel: 1
	}

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});
}

fnyDump.connectToGDrive = function() {
	var url = "connect_to_gdrive";
	jQuery(location).attr('href', fnyDbGetAjaxUrl(url));
}

fnyDump.checkBackupCreation = function() {

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'check_backup_creation'
	}

	jQuery.post(ajaxurl, data, function(response) {
		if (typeof response !== 'undefined') {
			location.reload();
		}
	});
};

fnyDump.getActionProgress = function() {

	var csrf_token = jQuery("#fny-backup-ajax-nonce").val();
	var data = {
		_ajax_nonce: csrf_token,
		action: 'check_action_status',
		id: jQuery('#fny-active-backup-id').val()
	}

	jQuery.post(ajaxurl, data, function(response) {

		response = jQuery.parseJSON(response);
		if (response != 0) {
			jQuery('#fny-progress-'+response.id).parent().show();
			jQuery('#fny-progress-'+response.id).css('width', response.progress+'%');

			var html = "Dumping...";
			if (response.type == FNY_DATABASE_BACKUP_TYPE_UPLOAD) {
				html = "Uploading..."
			}
			jQuery('#fny-progress-'+response.id).html(html);

			setTimeout(function () {
				fnyDump.getActionProgress();
			}, 2500);
		}
		else {
			location.reload();
		}
	});
};
