<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_superadmin($user_username);

$departments = get_departmentnames();
$is_superadmin = is_superadmin($user_username);

//create or edit admin
if(exists_and_not_empty('reason', $_POST)) {
    $user_id = $_POST['user'];
    $selected_departments = $_POST['states'] ?? [];

    if ($_POST['reason'] == "create") {
        create_admin($link, $user_id, $selected_departments);
        save_in_logs("INFO: Admin mit ID $user_id wurde erstellt", $user_firstname, $user_lastname);

    } else if ($_POST['reason'] == "edit") {
        delete_admin($link, $user_id);
        create_admin($link, $user_id, $selected_departments);
        save_in_logs("INFO: Admin mit ID $user_id wurde bearbeitet", $user_firstname, $user_lastname);
    }
}

// delete admin
if(exists_and_not_empty('remove_id', $_GET)) {
    $remove_id = $_GET['remove_id'];
    if ($remove_id) {
        delete_admin($link, $remove_id);
        save_in_logs("INFO: Der Admin mit der User ID $remove_id wurde entfernt", $user_firstname, $user_lastname);
    }
}

// delete admin
function delete_admin($link, $user_id) {
    $query = "DELETE FROM `admins` WHERE u_id=?";
    if ($stmt = mysqli_prepare($link, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $stmt->close();
    }
}

// create admin
function create_admin($link, $user_id, $departments = []) {
    foreach ($departments as $dept_id) {
        $query = "INSERT INTO admins (u_id, department) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $query)) {
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $dept_id);
            mysqli_stmt_execute($stmt);
            $stmt->close();
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
		<?php
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
	</div>
</body>
<?php
echo $OUTPUT->footer();
?>