<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_is_admin($user_username);

$is_superadmin = is_superadmin($user_username);

//remove
if(exists_and_not_empty('remove_id', $_GET)){
	if ($_GET['remove_id']) {
		$departments;
		$sql = "SELECT * FROM departments";
		if ($result = mysqli_query($link, $sql)) {
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result)) {
					$departments[$row['department_id']]['de'] = $row['department_de'];
					$departments[$row['department_id']]['en'] = $row['department_en'];
				}
				mysqli_free_result($result);
			}
		}

		$query = "DELETE FROM departments WHERE department_id=?";
		if ($stmt = mysqli_prepare($link, $query)) {
			mysqli_stmt_bind_param($stmt, "i", $_GET['remove_id']);

			if (!mysqli_stmt_execute($stmt)) {
				save_in_logs("ERROR: " . mysqli_error($link));
				save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
			} else {
				$text = "INFO: Institution '" . $departments[$_GET['remove_id']]['de'] . "' wurde erfolgreich gelöscht";
				save_in_logs($text, $user_firstname, $user_lastname, false);
				sendToast($text);
			}
		} else {
			save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
		}
		$stmt->close();

		unset($departments);
	}
}

//create
if(exists_and_not_empty('reason', $_POST)){
	if ($_POST['reason'] == "create") {
		$query = "INSERT INTO departments (department_de, department_en, announce1_de, announce1_en, mail, room) VALUES (?,?,?,?,?, ?)";
		if ($stmt = mysqli_prepare($link, $query)) {
			mysqli_stmt_bind_param($stmt, "ssssss", $_POST['department_de'], $_POST['department_en'], $_POST['announce1_de'], $_POST['announce1_en'], $_POST['mail'], $_POST['room']);

			if (!mysqli_stmt_execute($stmt)) {
				save_in_logs("ERROR: " . mysqli_error($link));
				save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
			} else {
				$text = "INFO: Die Instution '" . $_POST['department_de'] . "' wurde erfolgreich erstellt";
				save_in_logs($text, $user_firstname, $user_lastname, false);
				sendToast($text);
			}
		} else {
			save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
		}
		$stmt->close();
	}

	//edit
	if ($_POST['reason'] == "edit") {
		$query = "UPDATE departments SET department_de=?, department_en=?, announce1_de=?, announce1_en=?, room=?, mail=? WHERE department_id = ?";
		if ($stmt = mysqli_prepare($link, $query)) {
			mysqli_stmt_bind_param($stmt, "ssssssi", $_POST['department_de'], $_POST['department_en'], $_POST['announce1_de'], $_POST['announce1_en'], $_POST['room'], $_POST['mail'], $_POST['department_id']);

			if (!mysqli_stmt_execute($stmt)) {
				save_in_logs("ERROR: " . mysqli_error($link));
				save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
			} else {
				$text = "Die Instution '" . $_POST['department_de'] . "' wurde bearbeitet";
				save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
				sendToast($text);
			}
		} else {
			save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
		}
		$stmt->close();
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
	
	<!-- Stylesheet -->
	<link rel="stylesheet" href="style-css/rent.css">
	<link rel="stylesheet" href="style-css/toasty.css">
	<link rel="stylesheet" href="style-css/ahover.css">
	<link rel="stylesheet" href="style-css/accessability.css">
	<link rel="stylesheet" href="style-css/departments.css">
	<link rel="stylesheet" href="style-css/navbar.css">

	<!-- JavaScript -->
	<script src="js/clickablerow.js"></script>

	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

	<!-- Toast -->
	<?php require_once("Controller/toast.php"); ?>
</head>

<body>
	<div class="main">				
		<?php require_once 'navbar.php'; ?>	
		<br>
		<div class="mb-3">
			<input type="text" id="departmentSearch" class="form-control department" placeholder="Eintrag suchen...">
		</div>	
		<div id="departmentLinks">
		<?php
			$departments = get_departmentnames();
			$department_ids = get_admin_department($user_username);

			if (is_superadmin($user_username)) {
				echo "<a class='department' href='add_department'><i class='fas fa-plus'></i> " . translate('word_add') . "</a>";
				echo "<a class='department' href='edit_department.php?depart=" . $unassigned_institute . "'><i class='fas fa-times-circle'></i> " . $departments[$unassigned_institute]['de'] . "</a>";
			
				for ($i = 0; $i < count($departments); $i++) {
					if (array_keys($departments)[$i] == $unassigned_institute) continue;
					if (array_keys($departments)[$i] == $all_institutes) continue;
					echo "<a class='department' href='edit_department.php?depart=" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['de'] . "</a>";
				}
			} else {
				echo "<a class='department' href='edit_department.php?depart=" . $unassigned_institute . "'><i class='fas fa-times-circle'></i> " . $departments[$unassigned_institute]['de'] . "</a>";
				for ($i=0; $i < count($department_ids); $i++) { 
					echo "<a class='department' href='edit_department.php?depart=" . $department_ids[$i] . "'>" . $departments[$department_ids[$i]]['de'] . "</a>";
				}
			}
		?>
		</div>
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
<script>
	document.getElementById('departmentSearch').addEventListener('input', function () {
		const query = this.value.toLowerCase();
		const links = document.querySelectorAll('#departmentLinks .department');

		links.forEach(link => {
			const text = link.textContent.toLowerCase();
			link.style.display = text.includes(query) ? 'block' : 'none';
		});
	});
</script>
<?php
echo $OUTPUT->footer();
?>