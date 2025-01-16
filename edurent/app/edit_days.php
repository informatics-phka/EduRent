<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

$device_department= $_GET['depart'];
check_is_admin_of_department($user_username, $device_department);

//check working
if (isEmpty($_GET['day'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_days.php: _GET[day] isEmpty {" . $_GET['day'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

$limits = get_limits_of("rent_days");

$day;

$query = "SELECT time, dayofweek FROM rent_days WHERE id= ?";
if ($stmt = mysqli_prepare($link, $query)) {
	mysqli_stmt_bind_param($stmt, "i", $_GET["day"]);

	if (mysqli_stmt_execute($stmt)) {
		mysqli_stmt_store_result($stmt);
		if (mysqli_stmt_num_rows($stmt) > 0) {
			mysqli_stmt_bind_result($stmt, $time, $dayofweek);
			mysqli_stmt_fetch($stmt);
			$day['time'] = $time;
			$day['dayofweek'] = $dayofweek;
		} else {
			save_in_logs("ERROR: Kein Datensatz gefunden von dem abholtag gefunden (" . $query . ") 35");
			echo "<script>window.location.href = 'admini';</script>";
		}
	} else {
		save_in_logs("ERROR: " . mysqli_error($link));
		save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
	}
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

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
        <link rel="stylesheet" href="style-css/accessability.css">
		
		<!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    	
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
	</head>
	<div class="main">
		<h3><?php echo translate('word_pickupDay'); ?> '<?php echo $_GET["day"]; ?>' <?php echo translate('word_edit'); ?> </h3>
		<form action="days.php?depart=<?php echo $_GET["depart"];?>"method="post">
			<label for="day_name"><?php echo translate('word_dayOfWeek'); ?></label>
			<select class="form-select" name="day_name" id="day_name">
				<?php
				for ($i = 1; $i < 6; $i++) {
					$string = "weekday_long_" . $i;
					if ($day['dayofweek'] == $i) echo "<option selected value='" . $i . "'>" . $lang[$string] . "</option>";
					else echo "<option value='" . $i . "'>" . $lang[$string] . "</option>";
				}
				?>
			</select>
			<br>

			<div class="input-control">
				<label for="day_time"><?php echo translate('word_period'); ?></label>
				<input class="form-control rounded" type="text" maxlength="<?php echo $limits['time']; ?>" id="day_time" name="day_time" value="<?php echo $day['time']; ?>" placeholder="9:00-12:00">

				<div class="error error_text"></div>
			</div>

			<!-- hidden values -->
			<input type="hidden" id="day_id" name="day_id" value="<?php echo $_GET["day"]; ?>">
			<input type="hidden" id="reason" name="reason" value="edit">

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
			<div class='row justify-content-center'>
				<div class='col-md-6 mb-3'>
					<button type='button' class='btn btn-danger btn-block rounded mr-1 mb-1' onclick=window.location.href='days.php?remove_id=<?php echo $_GET["day"]; ?>&depart=<?php echo $_GET["depart"]; ?>'>
						<i class="fas fa-trash-alt mr-2"></i>
						<?php echo translate('word_delete'); ?>
					</button>
				</div>	
			</div>	
		</form>
	</div>
	<script>
		var time = document.getElementById("day_time");

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
		setSuccess(time);
	</script>
	<?php
	echo $OUTPUT->footer();
	?>
</body>