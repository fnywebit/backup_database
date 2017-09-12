<?php
if (!defined('ABSPATH')) {
	die();
}

$fnySchedulehours = array(
	'0'=>'12 Midnight',
	'1'=>'1 AM',
	'2'=>'2 AM',
	'3'=>'3 AM',
	'4'=>'4 AM',
	'5'=>'5 AM',
	'6'=>'6 AM',
	'7'=>'7 AM',
	'8'=>'8 AM',
	'9'=>'9 AM',
	'10'=>'10 AM',
	'11'=>'11 AM',
	'12'=>'12 Noon',
	'13'=>'1 PM',
	'14'=>'2 PM',
	'15'=>'3 PM',
	'16'=>'4 PM',
	'17'=>'5 PM',
	'18'=>'6 PM',
	'19'=>'7 PM',
	'20'=>'8 PM',
	'21'=>'9 PM',
	'22'=>'10 PM',
	'23'=>'11 PM'
);

$recurrence = (int)FNYBackupConfig::get("FNY_DATABASE_BACKUP_SCHEDULE_RECURRENCE")?(int)FNYBackupConfig::get("FNY_DATABASE_BACKUP_SCHEDULE_RECURRENCE"):FNY_DATABASE_BACKUP_RECURRENCE_DAY;
$hour = (int)FNYBackupConfig::get("FNY_DATABASE_BACKUP_SCHEDULE_HOUR");
$day = (int)FNYBackupConfig::get("FNY_DATABASE_BACKUP_SCHEDULE_DAY");

?>
<br>
<legend><?php echo _e('Set Up Schedule')?></legend>
<br>
<div class="container-fluid">
	<div class="row">
		<div id="fny-db-schedule-recurrence-div">
			<label for="fny-db-schedule-recurrence">Recurrence:</label>&nbsp;&nbsp;
			<select name="fny-db-schedule-recurrence" id="fny-db-schedule-recurrence">
				<option value="<?php echo FNY_DATABASE_BACKUP_RECURRENCE_DAY ?>" <?php echo ($recurrence == FNY_DATABASE_BACKUP_RECURRENCE_DAY)?'selected':'' ?>>Every day</option>
				<option value="<?php echo FNY_DATABASE_BACKUP_RECURRENCE_WEEK ?>" <?php echo ($recurrence == FNY_DATABASE_BACKUP_RECURRENCE_WEEK)?'selected':'' ?>>Every week</option>
				<option value="<?php echo FNY_DATABASE_BACKUP_RECURRENCE_MONTH ?>" <?php echo ($recurrence == FNY_DATABASE_BACKUP_RECURRENCE_MONTH)?'selected':'' ?>>Every month</option>
			</select>
		</div>
		<div id="fny-db-schedule-recurrence-hour-div" <?php echo ($recurrence != FNY_DATABASE_BACKUP_RECURRENCE_DAY)?'hidden':'' ?>>
			<br>
			<label for="fny-db-schedule-recurrence-hour">Hour:</label>&nbsp;&nbsp;
			<select name="fny-db-schedule-recurrence-hour" id="fny-db-schedule-recurrence-hour">
				<?php foreach($fnySchedulehours as $key => $scheduleHour): ?>
					<option value="<?php echo $key ?>" <?php echo ($hour == $key)?'selected':'' ?>><?php echo $scheduleHour ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div id="fny-db-schedule-recurrence-day-of-week-div" <?php echo ($recurrence != FNY_DATABASE_BACKUP_RECURRENCE_WEEK)?'hidden':'' ?>>
			<br>
			<label for="fny-db-schedule-recurrence-day-of-week">Day of week:</label>&nbsp;&nbsp;
			<select name="fny-db-schedule-recurrence-day-of-week" id="fny-db-schedule-recurrence-day-of-week">
				<option value="1" <?php echo ($day == 1)?'selected':'' ?>>Mon</option>
				<option value="2" <?php echo ($day == 2)?'selected':'' ?>>Tue</option>
				<option value="3" <?php echo ($day == 3)?'selected':'' ?>>Wed</option>
				<option value="4" <?php echo ($day == 4)?'selected':'' ?>>Thu</option>
				<option value="5" <?php echo ($day == 5)?'selected':'' ?>>Fri</option>
				<option value="6" <?php echo ($day == 6)?'selected':'' ?>>Sat</option>
				<option value="7" <?php echo ($day == 7)?'selected':'' ?>>Sun</option>
			</select>
		</div>
		<div id="fny-db-schedule-recurrence-day-of-month-div" <?php echo ($recurrence != FNY_DATABASE_BACKUP_RECURRENCE_MONTH)?'hidden':'' ?>>
			<br>
			<label for="fny-db-schedule-recurrence-day-of-month">Day of month:</label>&nbsp;&nbsp;
			<select name="fny-db-schedule-recurrence-day-of-month" id="fny-db-schedule-recurrence-day-of-month">
				<?php for ($i = 1; $i <= 31; $i++):?>
					<option value="<?php echo $i ?>" <?php echo ($day == $i)?'selected':'' ?>><?php echo $i ?></option>
				<?php endfor;?>
			</select>
		</div>
	</div>
</div>
<br>
<button id="fny-db-save-schedule-settings" class="btn btn-success">Save</button>
<button id="fny-db-remove-schedule" class="btn btn-danger">Remove</button>
