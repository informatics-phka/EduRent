<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check working
if (isEmpty($_GET['depart'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von add_type.php: _GET[depart] isEmpty {" . $_GET['depart'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

check_is_admin_of_department($user_username, $_GET['depart']);
$is_superadmin = is_superadmin($user_username);


//get data
$type = get_type_info();

//get limits
$limits = get_limits_of("device_type");

//get unic indicator
function get_combinations($chars, $length)
{
	$combis = array();
	if ($length > 0) {
		for ($i = 0; $i < strlen($chars); $i++) {
			$combis[count($combis)] = $chars[$i];
		}
		if ($length > 1) {
			for ($i = 0; $i < strlen($chars); $i++) {
				for ($o = 0; $o < strlen($chars); $o++) {
					$combis[count($combis)] = $chars[$i] . $chars[$o];
				}
			}
		}
	}
	return $combis;
}

$combinations = get_combinations("abcdefghijklmnopqrstuvwxyz", 2);
$keys = array_keys($type);
$unic;

for ($i = 0; $i < count($combinations); $i++) {
	for ($o = 0; $o < count($keys); $o++) {
		if ($combinations[$i] == $type[$keys[$o]]['indicator']) break;
		if ($o == count($keys) - 1) {
			$unic = $combinations[$i];
			break 2;
		}
	}
}
?>
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
	<link rel="stylesheet" href="style-css/accessability.css">
	<link rel="stylesheet" href="style-css/navbar.css">

	
	<!-- html editor -->
	<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
	<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
	
	<!-- Toast & Banner -->
	<?php require_once("Controller/toast.php"); ?>
</head>
<body>
	<div class="main">
		<?php require_once 'navbar.php'; ?>	
		<br>
		<h3><?php echo translate('text_pickup'); ?></h3>
		<form id="myForm" name="myForm" action="../Controller/simple_upload.php" method="post" enctype="multipart/form-data">

			<div class="input-control">
				<label for="device_type_name"><?php echo translate('text_deviceTypeName'); ?></label>
				<input class="form-control rounded" type="text" id="device_type_name" name="device_type_name" placeholder="iPad" maxlength='<?php echo $limits['device_type_name']; ?>'>

				<div class="error error_text"></div>
			</div>

			<div class="input-control">
				<label for="device_type_indicator"><?php echo translate('text_deviceTypeIndicator'); ?></label>
				<input class="form-control rounded" type="text" id="device_type_indicator" name="device_type_indicator" placeholder="ii" value='<?php echo $unic; ?>' maxlength='<?php echo $limits['device_type_indicator']; ?>'>

				<div class="error error_text"></div>
			</div>

			<div class="input-control">
				<label for="max_loan_days"><?php echo translate('text_deviceTypeMaxLoan'); ?></label>
				<input class="form-control rounded" id="max_loan_days" name="max_loan_days" type="text" placeholder="14" value="14" maxlength='<?php echo $limits['max_loan_days']; ?>'>

				<div class="error error_text"></div>
			</div>

			<label for="device_info"><?php echo translate('text_deviceTypeInfo'); ?></label>
			<input class="form-control rounded" type="text" id="device_info" name="device_info" placeholder="7th generation, Wi-Fi" maxlength='<?php echo $limits['device_type_info']; ?>'>
			<br>

			<div class="input-control">
				<label for="device_tooltip"><?php echo translate('text_deviceTypeTooltip'); ?></label>
				<div id="editor" name="editor">
				</div>

				<div class="error error_text"></div>
			</div>
			<br>

			<div class="mb-3">
				<label for="formFile" class="form-label"><?php echo translate('word_deviceTypeImg'); ?></label>
				<input class="form-control rounded" type="file" name="file" max-size="10" accept="image/png, image/jpeg, image/img">
			</div>

			<input type="hidden" class="form-control" id="department" name="department" value="<?php echo $_GET['depart']; ?>">

			<div class='row no-gutters text-center'>
				<div class='col'>
					<button type="submit" id="submit" class="btn rounded btn-success mr-1 mb-1"><?php echo translate('word_confirm'); ?></button>
				</div>
			</div>
		</form>
		<!-- Buttons -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-secondary btn-block' href='edit_department.php?depart=<?php echo $_GET['depart']; ?>'>
					<i class="fas fa-arrow-left mr-2"></i>
					<?php echo translate('word_back'); ?>
				</a>
			</div>
		</div>
</body>
<script>
	//Quill
	var options = {
		placeholder: 'Unsere iPads werden standardmäßig mit Stiften ausgestattet',
		theme: 'snow'
	};

	var editor = new Quill('#editor', options);

	var limit = <?php if (is_null($limits['tooltip'])) {
						echo "0";
					} else {
						echo $limits['tooltip'];
					} ?>;

	var editor_id = document.getElementById('editor');
	editor.on('text-change', function (delta, old, source) {
		if (editor.root.innerHTML.length > limit) {
			setError(editor_id, "Der Text ist zu lang");
			$('#submit').attr('disabled', 'disabled');
		} else {
			setSuccess(editor_id);
			check();
		}
	});

  	$('#myForm').submit(function(){ //listen for submit event
		$('<input />').attr('type', 'hidden')
			.attr('name', "editor")
			.attr('value', editor.root.innerHTML)
			.appendTo('#myForm');
		return true;
	}); 
	
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

	const name = document.getElementById('device_type_name');
	const inputHandler = function(e) {
		var error;
		if (!name.value) error = "<?php echo translate('text_deviceTypeNameError'); ?>";
		else if (!isUnic(name.value, "name")) error = "<?php echo translate('text_deviceTypeNameErrorUnic'); ?>";

		if (error) {
			setError(name, error);
			$('#submit').attr('disabled', 'disabled');
		} else {
			setSuccess(name);
			check();
		}
	}
	name.addEventListener('input', inputHandler);
	name.addEventListener('propertychange', inputHandler);

	const indic = document.getElementById('device_type_indicator');
	const inputHandler2 = function(e) {
		var error;
		if (!indic.value) error = "<?php echo translate('text_deviceTypeNameError'); ?>";
		else if (!isUnic(indic.value, "indicator")) error = "<?php echo translate('text_deviceTypeNameErrorUnic'); ?>";

		if (error) {
			setError(indic, error);
			$('#submit').attr('disabled', 'disabled');
		} else {
			setSuccess(indic);
			check();
		}
	}
	indic.addEventListener('input', inputHandler2);
	indic.addEventListener('propertychange', inputHandler2);

	setSuccess(indic);

	function only_numbers(value) {
		var checker = "[^0-9]";
		if (value.match(checker)) {
			return true
		} else return false
	}
	//todo: use $selected_department from session to check for max_loan_duration of the department
	/*if(exists_and_not_empty('selected_department', $_SESSION)){
    $selected_department = $_SESSION['selected_department'];
	}*/


	//check max_loan_days
	const loan_days = document.getElementById('max_loan_days');
	const inputHandler3 = function(e) {
		var error;
		if (!loan_days.value) error = "<?php echo translate('text_deviceTypeMaxLoanError'); ?>";
		if(only_numbers(loan_days.value)) error = "<?php echo translate('text_deviceTypeMaxLoanError'); ?>";
		if (error) {
			setError(loan_days, error);
			$('#submit').attr('disabled', 'disabled');
		} else {
			setSuccess(loan_days);
			check();
		}
	}
	loan_days.addEventListener('input', inputHandler3);
	loan_days.addEventListener('propertychange', inputHandler3);

	setSuccess(loan_days);

	function check() {
		if (name.parentElement.querySelector('.error').innerText.length == 1 &&
			loan_days.parentElement.querySelector('.error').innerText.length == 1 &&
			indic.parentElement.querySelector('.error').innerText.length == 1) {
			$('#submit').removeAttr('disabled');
		}
	}

	//start with errors
	$('#submit').attr('disabled', 'disabled');
	setError(name, "<?php echo translate('text_deviceTypeNameError'); ?>");

	var type_array = <?php echo is_null($type) ? "2" : json_encode($type); ?>;

	function isUnic(value, type) {
		const keys = Object.keys(type_array);
		if (type == "name") {
			for (let key of keys) {
				if (type_array[key]['name'] == value) {
					return false
				}
			}
			return true
		} else if (type == "indicator") {
			for (let key of keys) {
				if (type_array[key]['indicator'] == value) {
					return false
				}
			}
			return true
		}
	}
</script>
<?php
echo $OUTPUT->footer();
?>