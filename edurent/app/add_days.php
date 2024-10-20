<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check working
if (isEmpty($_GET['depart'])) {
	$SESSION->toasttext = "Fehler beim Aufrufen von add_days.php";
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von add_days.php: _GET[depart] isEmpty {" . $_GET['depart'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}
check_is_admin_of_department($user_username, $_GET['depart']);

//get data
$limits = get_limits_of("rent_days");
?>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<!-- JQuery -->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
	<script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

	<!-- Bootstrap -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	
	<!-- Stylesheet -->
	<link rel="stylesheet" href="style-css/rent.css">
	<link rel="stylesheet" href="style-css/toasty.css">
	
	<!-- Bootstrap Validator -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.bootstrapvalidator/0.5.2/css/bootstrapValidator.min.css" />
	<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script>
	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

	<!-- Toast -->
	<?php require_once("Controller/toast.php"); ?>
</head>
<body>
	<div class="main">
		<h3 class="text-center">Title</h3>
		<form id="form" action="days.php?depart=<?php echo $_GET["depart"];?>" method="post">
			<label for="day_name"><?php echo translate('word_dayOfWeek'); ?></label>
			<select class="form-select" name="day_name" id="day_name">
				<?php
				//saturday & sunday disabled
				for ($i = 0; $i < 5; $i++) {
					$wert = $i + 1;
					$string = "weekday_long_" . $wert;
					if ("1" == $wert) echo "<option selected value='" . $wert . "'>" . translate($string) . "</option>";
					else echo "<option value='" . $wert . "'>" . translate($string) . "</option>";
				}
				?>
			</select>
			<br>

			<div class="input-control">
				<label for="day_time"><?php echo translate('word_period'); ?></label>
				<input class="form-control rounded" type="text" maxlength="<?php echo $limits['time']; ?>" id="day_time" name="day_time" value="" placeholder="9:00-12:00">
				
				<div class="error error_text"></div>
			</div>

			<input type="hidden" class="form-control" id="create" name="create" value="1">

			<br>
			<!-- Buttons -->
			<div class='row justify-content-center'>
				<div class='col-md-6 mb-3'>
					<a class='btn btn-secondary btn-block' href='days.php?depart=<?php echo $_GET["depart"]; ?>'>
						<i class="fas fa-arrow-left mr-2"></i>
						<?php echo translate('word_back'); ?>
					</a>
				</div>
				<div class='col-md-6 mb-3'>
                    <button type='submit' id="submit" class='btn btn-success btn-block rounded mr-1 mb-1'>
                        <i class="fas fa-save mr-2"></i>
                        <?php echo translate('word_save'); ?>
                    </button>
                </div>
            </div>
		</form>
	</div>
</body>
<script>
	var time = document.getElementById("day_time");
	var title = <?php echo json_encode(translate('text_createPickup')); ?>;

	document.getElementById("title").innerText = title;

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

	const inputHandler = function(e) {
		var error;
		if (!time.value) error = "<?php echo translate('text_timeError'); ?>";

		if (error) {
			setError(time, error);
			$('#submit').attr('disabled', 'disabled');
		} else {
			setSuccess(time);
			$('#submit').removeAttr('disabled');
		}
	}
	time.addEventListener('input', inputHandler);
	time.addEventListener('propertychange', inputHandler);

	setError(time, "<?php echo translate('text_timeError'); ?>");
	$('#submit').attr('disabled', 'disabled');
</script>
<?php
echo $OUTPUT->footer();
?>