<!DOCTYPE HTML>
<?php
/*
  Upload a file to the server and save the path in mysql
*/

//require
require_once("Controller/db_connect.php");
require_once("Controller/error.php");
require_once("Controller/functions.php");
require_once("Controller/database.php");

//get data
$departments = get_departmentnames();

$old_departments = array();

$query = "SELECT department_id FROM type_department WHERE type_id = ?";
if ($stmt = mysqli_prepare($link, $query)) {
	mysqli_stmt_bind_param($stmt, "i", $_POST['old_type']);

	if (mysqli_stmt_execute($stmt)) {
		mysqli_stmt_store_result($stmt);
		if (mysqli_stmt_num_rows($stmt) > 0) {
			mysqli_stmt_bind_result($stmt, $department_id);
			while (mysqli_stmt_fetch($stmt)) {
				$old_departments[count($old_departments)] = $department_id;
			}
		} else {
			save_in_logs("ERROR: Kein Datensatz gefunden (" . $query . ") 30");
		}
	} else {
		save_in_logs("ERROR: " . mysqli_error($link));
		save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
	}
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

$new_departments = array();
for ($i = 0; $i < count($departments); $i++) {
	$name = "switch_" . array_keys($departments)[$i];
	if ($_POST[$name] == "on") {
		$new_departments[count($new_departments)] = array_keys($departments)[$i];
	}
}

$fehlt;
if ($old_departments == null) {
	$fehlt = $new_departments;
} else {
	$fehlt = array_diff($new_departments, $old_departments);
}
$zuviel = array_diff($old_departments, $new_departments);

for ($i = 0; $i < count($fehlt); $i++) {
	//sqlinjection
	$sql = "INSERT INTO `type_department`(`type_id`, `department_id`) VALUES (" . $_POST['old_type'] . ", " . $fehlt[array_keys($fehlt)[$i]] . ")";
	if (mysqli_query($link, $sql)) {
		//success
	} else {
		$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
		break;
	}
}

for ($i = 0; $i < count($zuviel); $i++) {
	//sqlinjection
	$sql = "DELETE FROM `type_department` WHERE type_id=" . $_POST['old_type'] . " AND department_id=" . $zuviel[array_keys($zuviel)[$i]];
	if (mysqli_query($link, $sql)) {
		//success
	} else {
		$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
		break;
	}
}

$device_info = $_POST['device_info'];
$device_type_indicator = $_POST['device_type_indicator'];
$device_tooltip = $_POST['editor'];
$device_department = $_POST['department'];
$device_type = $_POST['old_type'];
$device_type_name = $_POST['device_type_name'];
$device_home_department = $_POST['home_department'];
$device_type_storage = $_POST['storage_info'];
$max_loan_days = $_POST['max_loan_days'];

$hasImage = isset($_FILES['file']) && !empty($_FILES['file']['name']);
$isEdit = isset($_POST['old_type']);
$device_type_id = $_POST['old_type'] ?? null;
$deleteImage = isset($_POST['delete_image']) && $_POST['delete_image'] == '1';

if ($hasImage) {
	$filename = $_FILES['file']['name'];
	$location = "img/" . $filename;

	if (file_exists($location)) {
		$location = get_new_name($filename);
	}

	if (!file_exists($location)) {
		if (!copy($_FILES['file']['tmp_name'], $location)) {
			$errors = error_get_last();
			error_to_superadmin(get_superadmins(), $mail, "ERROR: Upload fehlgeschlagen – {$errors['type']} {$errors['message']}");
			exit;
		}
	} else {
		error_to_superadmin(get_superadmins(), $mail, "ERROR: Datei existiert bereits – $filename");
		exit;
	}
}

if ($isEdit) {
	$sql = "UPDATE device_type SET 
		device_type_indicator = '$device_type_indicator',
		device_type_name = '$device_type_name',
		home_department = '$device_home_department',
		max_loan_days = '$max_loan_days'";

	if ($hasImage) {
		$sql .= ", device_type_img_path = '$location'";
	} elseif ($deleteImage) {
		// Optional: Bilddatei löschen, falls vorhanden
		$res = mysqli_query($link, "SELECT device_type_img_path FROM device_type WHERE device_type_id = $device_type_id");
		if ($res && $row = mysqli_fetch_assoc($res)) {
			if (!empty($row['device_type_img_path']) && file_exists($row['device_type_img_path'])) {
				unlink($row['device_type_img_path']);
			}
		}
		$sql .= ", device_type_img_path = NULL";
	}
	
	$sql .= empty($device_info)     ? ", device_type_info = NULL"     : ", device_type_info = '$device_info'";
	$sql .= empty($device_tooltip)  ? ", tooltip = NULL"              : ", tooltip = '$device_tooltip'";
	$sql .= empty($device_type_storage) ? ", device_type_storage = NULL" : ", device_type_storage = '$device_type_storage'";
	$sql .= " WHERE device_type_id = $device_type_id";

	if (!mysqli_query($link, $sql)) {
		$error = "ERROR: Could not execute: $sql : " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
	}
	echo "<script>location.href = 'edit_type.php?reason=edit&type=$device_type';</script>";

} else {
	// add new device type
	$columns = "device_type_indicator, device_type_name, device_type_info, tooltip, home_department, max_loan_days";
	$values = "'$device_type_indicator', '$device_type_name', " .
		($device_info     ? "'$device_info'"     : "NULL") . ", " .
		($device_tooltip  ? "'$device_tooltip'"  : "NULL") . ", " .
		"'$device_department', '$max_loan_days'";

	if ($hasImage) {
		$columns .= ", device_type_img_path";
		$values  .= ", '$location'";
	}

	$sql = "INSERT INTO device_type ($columns) VALUES ($values)";

	if (mysqli_query($link, $sql)) {
		$type_id = mysqli_insert_id($link);
		$sql2 = "INSERT INTO type_department (type_id, department_id) VALUES ('$type_id', '$device_department')";

		if (mysqli_query($link, $sql2)) {
			echo "<script>location.href = 'edit_department.php?reason=create&depart=$device_department';</script>";
		} else {
			$error = "ERROR: Konnte Zuordnung nicht speichern: $sql2 : " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	} else {
		$error = "ERROR: Konnte Gerätetyp nicht speichern: $sql : " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
	}
}

function get_new_name($filename)
{
	//remove filetyp
	$parts = explode(".", $filename);
	$number = rand(0, 9);
	$filename = $parts[0] . $number . "." . $parts[1];
	$location = "img/" . $filename;
	if (file_exists($location)) {
		get_new_name($filename);
	} else {
		return $location;
	}
}
?>