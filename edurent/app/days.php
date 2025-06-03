<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check working
if (isEmpty($_GET['depart'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von days.php: _GET isEmpty {" . $_GET['type'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

check_is_admin_of_department($user_username, $_GET['depart']);
$is_superadmin = is_superadmin($user_username);


$depart = $_GET["depart"];

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
	<link rel="stylesheet" href="style-css/ahover.css">
	<link rel="stylesheet" href="js/clickablerow.css">
	<link rel="stylesheet" href="style-css/accessability.css">
	<link rel="stylesheet" href="style-css/navbar.css">

	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
	
	<!-- Toast -->
	<?php require_once("Controller/toast.php"); ?>
</head>
<body>	
	<div class="main">
		<?php require_once 'navbar.php'; ?>	
		<br>
		<h3 class="text-center"><?php echo translate('word_pickupDays'); ?></h3>
		<?php
		//Create
		if(exists_and_not_empty('create', $_POST)){
			$query = "INSERT INTO rent_days (time, dayofweek, d_id) VALUES (?,?,?)";
			if ($stmt = mysqli_prepare($link, $query)) {
				mysqli_stmt_bind_param($stmt, "sii", $_POST['day_time'], $_POST['day_name'], $depart);

				if (!mysqli_stmt_execute($stmt)) {
					save_in_logs("ERROR: " . mysqli_error($link));
					save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
				} else {
					$text = "INFO: Der Abholtag wurde erfolgreich erstellt";
					save_in_logs($text, $user_firstname, $user_lastname, false);
					$SESSION->toasttext = $text;
					echo "<script>location.href = '" . $_SERVER['REQUEST_URI']. "';</script>";
				}
			} else save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
			$stmt->close();
		}

		//Edit
		if(exists_and_not_empty('day_name', $_POST) && exists_and_not_empty('reason', $_POST)){
			if ($_POST['reason'] == "edit") {
				$query = "UPDATE rent_days SET time = ?, dayofweek = ? WHERE id=?";
				if ($stmt = mysqli_prepare($link, $query)) {
					mysqli_stmt_bind_param($stmt, "sii", $_POST['day_time'], $_POST['day_name'], $_POST['day_id']);

					if (!mysqli_stmt_execute($stmt)) {
						save_in_logs("ERROR: " . mysqli_error($link));
						save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
					} else {
						$text = "Der Abholtag mit der ID " . $_POST['day_id'] . " wurde bearbeitet";
						save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
						sendToast($text);
					}
				} else save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
				$stmt->close();
			}
		}

		//Remove
		if(exists_and_not_empty('remove_id', $_GET)){
			$days;
			$sql = "SELECT * FROM rent_days";
			if ($result = mysqli_query($link, $sql)) {
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result)) {
						$string = "weekday_long_" . $row['dayofweek'];
						$days[$row['id']]["day"] = translate($string);
						$days[$row['id']]['time'] = $row['time'];
						$days[$row['id']]['dayofweek'] = $row['dayofweek'];
					}
					mysqli_free_result($result);
				}
			}

			$query = "DELETE FROM `rent_days` WHERE id=?";
			if ($stmt = mysqli_prepare($link, $query)) {
				mysqli_stmt_bind_param($stmt, "i", $_GET['remove_id']);

				if (!mysqli_stmt_execute($stmt)) {
					save_in_logs("ERROR: " . mysqli_error($link));
					save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
				} else {
					$text = "INFO: Der Abholtag '" . $_GET['remove_id'] . "' wurde erfolgreich gelöscht";
					save_in_logs($text, $user_firstname, $user_lastname, false);
					$SESSION->toasttext = $text;
					$url = str_replace("remove_id=" . $_GET['remove_id'] . "&", "", $_SERVER['REQUEST_URI']);
					echo "<script>location.href = '$url';</script>";
				}
			} else {
				save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
			}
			$stmt->close();

			unset($days);
		}

		$days;
		$query = "SELECT time, dayofweek, id FROM rent_days WHERE d_id = ? ORDER BY dayofweek";
		if ($stmt = mysqli_prepare($link, $query)) {
			mysqli_stmt_bind_param($stmt, "i", $_GET["depart"]);

			if (mysqli_stmt_execute($stmt)) {
				mysqli_stmt_store_result($stmt);
				if (mysqli_stmt_num_rows($stmt) > 0) {
					mysqli_stmt_bind_result($stmt, $time, $dayofweek, $id);
					while (mysqli_stmt_fetch($stmt)) {
						$string = "weekday_long_" . $dayofweek;
						$days[$id]["day"] = translate($string);
						$days[$id]['time'] = $time;
						$days[$id]['dayofweek'] = $dayofweek;
					}
				} else {
					save_in_logs("INFO: Kein Datensatz gefunden (" . $query . ") days: 136", "Server", "", false);
				}
			} else {
				save_in_logs("ERROR: " . mysqli_error($link));
				save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
			}
		} else {
			save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
		}
		$stmt->close();

		//Tabel - START
		?>

		<div class='row justify-content-center'>
			<div class='col-12 mb-3'>
				<a href='add_days.php?depart=<?php echo $_GET["depart"]; ?>' class='btn btn-outline-dark btn-block'>
				<i class="fa-regular fa-calendar-plus mr-2"></i></i><?php echo translate('word_add'); ?>
				</a>
			</div>
		</div>
		<div class='row justify-content-center'>
			<?php
			if(isset($days)){
				for ($i = 0; $i < count($days); $i++) {
					echo "<div class='col-12 col-md-6 mb-3'>";
						echo "<a href='edit_days.php?&day=" . array_keys($days)[$i] . "&depart=" . $_GET["depart"] . "' class='btn btn-outline-dark btn-block'>" . $days[array_keys($days)[$i]]['day'] . " " . $days[array_keys($days)[$i]]['time'] . "</a>";
					echo "</div>";
				}
			}
			?>
		</div>
		<!-- Tabel - END -->

		<br>
		<!-- Buttons -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-secondary btn-block' href='edit_department.php?depart=<?php echo $_GET["depart"]; ?>'>
					<i class="fas fa-arrow-left mr-2"></i>
					<?php echo translate('word_back'); ?>
				</a>
			</div>
		</div>
	</div>
</body>

<?php
echo $OUTPUT->footer();
?>