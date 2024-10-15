<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_superadmin($user_username);

$departments = get_departmentnames();

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
		<link rel="stylesheet" href="style-css/ahover.css">
		<link rel="stylesheet" href="js/clickablerow.css">
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
	</head>
	<div class="main">
	<h3 class="text-center"><?php echo translate('word_admins') ?></h3>
		<?php
		if(exists_and_not_empty('reason', $_POST)){
			if ($_POST['reason'] == "create") { //create admin
				for ($i = 0; $i < count($departments); $i++) {
					$name = "switch_" . array_keys($departments)[$i];
					if(exists_and_not_empty($name, $_POST)){
						if ($_POST[$name] == "on") {
							$query = "INSERT INTO admins (u_id, department) VALUES (?,?)";
							if ($stmt = mysqli_prepare($link, $query)) {
								mysqli_stmt_bind_param($stmt, "ii", $_POST['user'], array_keys($departments)[$i]);

								if (!mysqli_stmt_execute($stmt)) {
									save_in_logs("ERROR: " . mysqli_error($link));
									save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
								}
							} else {
								save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
							}
							$stmt->close();
						}
					}
				}
			}
			else if ($_POST['reason'] == "edit") { //edit admin
				$query = "DELETE FROM `admins` WHERE u_id=?";
				if ($stmt = mysqli_prepare($link, $query)) {
					mysqli_stmt_bind_param($stmt, "i", $_POST['user']);

					if (!mysqli_stmt_execute($stmt)) {
						save_in_logs("ERROR: " . mysqli_error($link));
						save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
					}
				} else {
					save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
				}
				$stmt->close();

				for ($i = 0; $i < count($departments); $i++) {
					$name = "switch_" . array_keys($departments)[$i];
					if(exists_and_not_empty($name, $_POST)){
						if(exists_and_not_empty($name, $_POST)){
							if($_POST[$name] == "on") {
								$query = "INSERT INTO admins (u_id, department) VALUES (?,?)";
								if ($stmt = mysqli_prepare($link, $query)) {
									mysqli_stmt_bind_param($stmt, "ii", $_POST['user'], array_keys($departments)[$i]);

									if (!mysqli_stmt_execute($stmt)) {
										save_in_logs("ERROR: " . mysqli_error($link));
										save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
									}
								} else {
									save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
								}
								$stmt->close();
							}
						}
					}
				}
				save_in_logs("INFO: Der Benutzer mit der ID " . $_POST['user'] . " wurde bearbeitet", $user_firstname, $user_lastname);
			}
		}

		if(exists_and_not_empty('remove_id', $_GET)){
			if ($_GET['remove_id']) { //remove admin
				$query = "DELETE FROM `admins` WHERE u_id=?";
				if ($stmt = mysqli_prepare($link, $query)) {
					mysqli_stmt_bind_param($stmt, "i", $_GET['remove_id']);

					if (!mysqli_stmt_execute($stmt)) {
						save_in_logs("ERROR: " . mysqli_error($link));
						save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
					}
				} else {
					save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
				}
				$stmt->close();
				save_in_logs("INFO: Der Admin mit der User ID " . $_GET['remove_id'] . " wurde entfernt",  $user_firstname, $user_lastname);
			}
		}

		check_superadmin($user_username);

		$admins = get_all_admins();

		echo "<div class='row justify-content-center'>";
			echo "<div class='col-12 mb-3'>";
				echo "<a href='add_admins' class='btn btn-outline-dark btn-block'><i class='fas fa-user-plus mr-2'></i>" . translate('word_add') . "</a>";
			echo "</div>";
		echo "</div>";
		echo "<div class='row justify-content-center'>";

		for ($i = 0; $i < count($admins); $i++) {
			echo "<div class='col-12 col-md-6 mb-3'>";
			echo "<a href='edit_admins?u_id=" . array_keys($admins)[$i] . "' class='btn btn-outline-dark btn-block'>" . $admins[array_keys($admins)[$i]]['fn'] . " " . $admins[array_keys($admins)[$i]]['ln'] . "</a>";
			echo "</div>";
		}
	
		echo "</div>";

		?>
		<br>
		<!-- Buttons -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-secondary btn-block' href='admini'>
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