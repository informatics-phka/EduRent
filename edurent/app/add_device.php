<!DOCTYPE HTML>
<?php
//show errors     
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check working
if (isEmpty($_GET['type'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von add_device.php: _GET[type] isEmpty {" . $_GET['type'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

//get data
$selected_type_id = $_GET['type'];

$type = get_type_info();
$ids = get_all_device_ids();
$block = get_blocked_devices();

$device_department= $type[$_GET['type']]['home_department'];
check_is_admin_of_department($user_username, $device_department);

//get limits
$limits = get_limits_of("device_list");

if(array_key_exists($selected_type_id, $ids)){
	$number = intval(max($ids[$selected_type_id]) + 1);
}
else{
	$number = 1;
}

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
		
		<!-- Bootstrap Validator -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.bootstrapvalidator/0.5.2/css/bootstrapValidator.min.css" />
		<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script>
	</head>
	<div class="main">
		<h3 style='text-align:center; width:100%;'><?php echo translate('text_edittype', ['a' => $type[$selected_type_id]['name']]) ?></h3>
		<br>
		<form class="needs-validation" id="form" action="edit_type.php?type=<?php echo $selected_type_id; ?>" method="post" novalidate>
			<div class='row no-gutters' style='text-align:center;'>
				<div class='col'>
					<label for="device_tag"><?php echo translate('text_enterDevicetag'); ?></label>
				</div>
				<div class='col'>
					<div class="input-group mb-3">
						<div class="input-group-prepend">
							<span class="input-group-text"><?php echo $type[$_GET['type']]['indicator']; ?></span>
						</div>
						<input id="device_tag" class="form-control" name="device_tag" type="text" placeholder="device_tag eingeben" value="<?php echo $number; ?>" maxlength='<?php echo $limits['device_tag']; ?>'>
						<div class="error error_text"></div>
					</div>
				</div>
			</div>
			<br>
			<div class='row no-gutters' style='text-align:center;'>
				<div class='col'>
					<label for="serialnumber"><?php echo translate('text_enterSerialnumber'); ?></label>
				</div>
				<div class='col'>
					<input type="text" class="form-control" id="serialnumber" name="serialnumber" placeholder='<?php echo translate('text_enterserialnumber'); ?>' maxlength='<?php echo $limits['serialnumber']; ?>'>
				</div>
			</div>
			<br>
			<div class='row no-gutters' style='text-align:center;'>
				<div class='col'>
					<label for="blocked"><?php echo translate('word_blocked'); ?></label>
				</div>
				<div class='col'>
					<select class="form-select" name="blocked" id="blocked">
						<?php
						for ($i = 0; $i < count($block); $i++) {
							if (0 == array_keys($block)[$i]) echo "<option selected value='" . array_keys($block)[$i] . "'>" . $block[$i] . "</option>";
							else echo "<option value='" . array_keys($block)[$i] . "'>" . $block[$i] . "</option>";
						}
						?>
					</select>
				</div>
			</div>
			<br>

			<!-- hidden values -->
			<input type="hidden" class="form-control" id="id" name="id" value="<?php echo $selected_type_id; ?>">
			<input type="hidden" class="form-control" id="create" name="create" value="1">

			<!-- Buttons -->
			<div class='row justify-content-center'>
				<div class='col-md-6 mb-3'>
					<a class='btn btn-secondary btn-block' href='edit_type.php?type=<?php echo $selected_type_id; ?>'>
						<i class="fas fa-arrow-left mr-2"></i>
						<?php echo translate('word_back'); ?>
					</a>
				</div>
				<div class='col-md-6 mb-3'>
					<button type="button" class="btn btn-success btn-block" disabled onclick='document.form.submit()'><?php echo translate('word_confirm'); ?></button>
				</div>
			</div>
		</form>	
	</body>
	<script>
		//Check for valide input
		var devices_array = <?php echo is_null($ids) ? "2" : json_encode($ids); ?>;
		var device_type = <?php echo is_null($selected_type_id) ? "2" : json_encode($selected_type_id); ?>;

		//errorhandle
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

		const tag = document.getElementById('device_tag');

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

		tag.addEventListener('input', inputHandler);
		tag.addEventListener('propertychange', inputHandler);

		function isUnic(value) {
			if (devices_array[selected_type_id] === undefined) {
				return true
			}
			if (devices_array[selected_type_id].includes(value)) {
				return false
			}
			return true
		}

		function isValid(value) {
			var checker = "^[0-9]+$";
			if (value.match(checker)) {
				return true
			} else return false
		}
	</script>
<?php
echo $OUTPUT->footer();
?>