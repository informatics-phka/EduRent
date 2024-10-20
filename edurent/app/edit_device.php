<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check working
if (isEmpty($_GET['type'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_device.php: _GET[type] isEmpty {" . $_GET['type'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

if (isEmpty($_GET['device'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_device.php: _GET[device] isEmpty {" . $_GET['device'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

//get data
$type = get_type_info();
$devices;
$block = get_blocked_devices();
$limits = get_limits_of("device_list");

$sql = "SELECT * FROM device_list ORDER BY device_id";
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$devices[$row['device_type_id']][$row['device_id']][0] = $row['device_tag'];
			$devices[$row['device_type_id']][$row['device_id']][1] = $row['serialnumber'];
			$devices[$row['device_type_id']][$row['device_id']][2] = $row['blocked'];
			$devices[$row['device_type_id']][$row['device_id']][3] = $row['device_id'];
			$devices[$row['device_type_id']][$row['device_id']][4] = $row['device_type_id'];
			$devices[$row['device_type_id']][$row['device_id']][5] = $row['note'];
		}
		mysqli_free_result($result);
	} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
}

$selected_type_id = $_GET['type'];
$selected_id = -1;
if(!isset($devices[$selected_type_id])){
	$devices = NULL;
	error_to_superadmin(get_superadmins(), $mail, "selected_type_id not found in devices");
}
else{
	for ($i = 0; $i < count($devices[$selected_type_id]); $i++) {
		if ($devices[$selected_type_id][array_keys($devices[$selected_type_id])[$i]][0] == $_GET['device']) $selected_id = $devices[$selected_type_id][array_keys($devices[$selected_type_id])[$i]][3];
	}
}

$device_department= $type[$_GET['type']]['home_department'];
check_is_admin_of_department($user_username, $device_department);
?>
<body>

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		
		<!-- JQuery -->
		<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
		<script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
		
		<!-- Bootstrap -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
		<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
		
		<!-- stylesheet -->
		<link rel="stylesheet" href="style-css/rent.css">
        <link rel="stylesheet" href="style-css/toasty.css">
		
		<!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    	
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
	</head>
	<div class="main">
		<h3><?php echo translate('text_enterDevicetag'); ?> '<?php echo $type[$_GET['type']]['name'] . $_GET['device']; ?>' <?php echo translate('word_edit'); ?></h3>
		<form id="form" name="form" action="edit_type.php?type=<?php echo $_GET['type']; ?>" method="post">
			<label for="serialnumber"><?php echo translate('text_enterSerialnumber'); ?></label>
			<input type="text" class="form-control rounded" id="serialnumber" name="serialnumber" value="<?php echo $devices[$selected_type_id][$selected_id][1]; ?>" maxlength='<?php echo $limits['serialnumber']; ?>'>
			<br>

			<label for="type"><?php echo translate('word_type'); ?></label>
			<select class="form-select" onchange="type_changed()" name="type" id="type">
				<?php
				for ($i = 0; $i < count($type); $i++) {
					if ($selected_type_id == array_keys($type)[$i]) echo "<option selected value='" . array_keys($type)[$i] . "'>" . $type[array_keys($type)[$i]]['name'] . "</option>";
					else echo "<option value='" . array_keys($type)[$i] . "'>" . $type[array_keys($type)[$i]]['name'] . "</option>";
				}
				?>
			</select>
			<br>

			<label for="tag"><?php echo translate('text_enterDevicetag'); ?></label>
			<div class="input-group mb-3">
				<div class="input-group-prepend">
					<span class="input-group-text"><?php echo $type[$_GET['type']]['indicator']; ?></span>
				</div>
				<input id="tag" name="tag" class="form-control" type="text" value="<?php echo $devices[$selected_type_id][$selected_id][0]; ?>" maxlength='<?php echo $limits['device_tag']; ?>'>
			</div>
			<div class="error error_text"></div>

			<label for="blocked"><?php echo translate('word_blocked'); ?></label>
			<select class="form-select" name="blocked" id="blocked">
				<?php
				for ($i = 0; $i < count($block); $i++) {
					if ($devices[$selected_type_id][$selected_id][2] == array_keys($block)[$i]) echo "<option selected value='" . array_keys($block)[$i] . "'>" . $block[$i] . "</option>";
					else echo "<option value='" . array_keys($block)[$i] . "'>" . $block[$i] . "</option>";
				}
				?>
			</select>
			<br>

			<label for="note">Notiz</label>
			<textarea class="form-control rounded" id="note" name="note" maxlength='<?php echo $limits['note']; ?>'><?php echo $devices[$selected_type_id][$selected_id][5]; ?></textarea>

			<!-- hidden values -->
			<input type="hidden" class="form-control" id="id" name="id" value="<?php echo $selected_id; ?>">
			<input type="hidden" class="form-control" id="reason" name="reason" value="edit">
			<br>

			<!-- Buttons -->
			<div class='row justify-content-center'>
				<div class='col-md-6 mb-3'>
					<a class='btn btn-secondary btn-block' href='edit_type.php?type=<?php echo $selected_type_id; ?>'>
						<i class="fas fa-arrow-left mr-2"></i>
						<?php echo translate('word_back'); ?>
					</a>
				</div>
				<div class='col-md-6 mb-3'>
					<button type="button" class="btn btn-success btn-block" onclick='document.form.submit()'><?php echo translate('word_save'); ?></button>
				</div>
			</div>
		</form>
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-danger btn-block rounded' href='edit_type.php?type=<?php echo $selected_type_id; ?>&remove_id=<?php echo $selected_id; ?>&selected_type_id=<?php echo $selected_type_id; ?>&device_tag=<?php echo $devices[$selected_type_id][$selected_id][0]; ?>'>
					<i class="fas fa-trash-alt mr-2"></i>
					<?php echo translate('word_delete'); ?>
				</a>
			</div>
		</div>
	</div>
</body>
<script type="text/javascript">
	var devices_array = <?php echo is_null($devices) ? "2" : json_encode($devices); ?>;
	var type_array = <?php echo is_null($type) ? "2" : json_encode($type); ?>;
	var selected_id = <?php echo is_null($selected_id) ? "-1" : json_encode($selected_id); ?>;
	var selected_type_id = <?php echo is_null($selected_type_id) ? "-1" : json_encode($selected_type_id); ?>;

	var old_device_id = devices_array[selected_type_id][selected_id][0];
	var old_type_id = selected_type_id;

	const tag = document.getElementById('tag');

	//if tag changes check for errors
	const source = document.getElementById('tag');
	const inputHandler = function(e) {
		var error;
		if (!tag.value) error = "Bitte geben Sie einen einzigartigen Geräte-Tag ein";
		else if (!isValid(tag.value)) error = "Es sind nur Zahlen erlaubt";
		else if (!isUnic(tag.value)) error = "Dieser Geräte-Tag ist bereits vergeben";

		if (error) {
			setError(tag.parentElement, error);
			$('#submit').attr('disabled', 'disabled');
		} else {
			setSuccess(tag.parentElement);
			$('#submit').removeAttr('disabled');
		}
	}
	source.addEventListener('input', inputHandler);
	source.addEventListener('propertychange', inputHandler);

	//errors
	const setError = (element, message) => {
		const inputControl = element.parentElement;
		const errorDisplay = inputControl.querySelector('.error');

		errorDisplay.innerText = message;
		inputControl.classList.add('error');
		inputControl.classList.remove('success')
	}

	const setSuccess = element => {
		const inputControl = element.parentElement;
		const errorDisplay = inputControl.querySelector('.error');

		errorDisplay.innerHTML = '&nbsp;';
		inputControl.classList.add('success');
		inputControl.classList.remove('error');
	};

	setSuccess(tag.parentElement);

	function isUnic(device_tag) {
		var select = document.getElementById('type');
		var value = select.options[select.selectedIndex].value;

		if (device_tag == old_device_id && old_type_id == value) return true; //name not changed

		for (let i = 0; i < Object.keys(devices_array[value]).length; i++) { //name unic
			if (devices_array[value][Object.keys(devices_array[value])[i]][0] == device_tag) return false;
		}
		return true;
	}

	function isValid(value) {
		if (value.match("^[0-9]+$")) return true
		else return false;
	}
</script>

<?php
echo $OUTPUT->footer();
?>