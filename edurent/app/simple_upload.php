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
	mysqli_stmt_close($stmt);
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}

$new_departments = array();
for ($i = 0; $i < count($departments); $i++) {
	$name = "switch_" . array_keys($departments)[$i];
	if (isset($_POST[$name]) && $_POST[$name] === "on") {
		$new_departments[count($new_departments)] = array_keys($departments)[$i];
	}
}

$type_id = (int) $_POST['old_type'];

$missing = ($old_departments === null) ? $new_departments : array_diff($new_departments, $old_departments);
$excess = array_diff($old_departments ?? [], $new_departments);

// Add missing department associations
foreach ($missing as $dept_id) {
	$department_id = (int) $dept_id;
	$sql = "INSERT INTO `type_department` (`type_id`, `department_id`) VALUES ($type_id, $department_id)";

	if (!mysqli_query($link, $sql)) {
		$error = "ERROR: Could not execute INSERT: $sql : " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
		break;
	}
}

// Remove excess department associations
foreach ($excess as $dept_id) {
	$department_id = (int) $dept_id;
	$sql = "DELETE FROM `type_department` WHERE `type_id` = $type_id AND `department_id` = $department_id";

	if (!mysqli_query($link, $sql)) {
		$error = "ERROR: Could not execute DELETE: $sql : " . mysqli_error($link);
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
    $parts = explode(".", $filename);
    $base_name = $parts[0];
    $extension = $parts[1];

    $new_filename = $base_name . substr(bin2hex(random_bytes(6)), 0, 8) . "." . $extension;
    $location = "img/" . $new_filename;

    // Limit attempts to 10
    $attempts = 0;
    while (file_exists($location) && $attempts < 10) {
        $new_filename = $base_name . rand(0, 9) . "." . $extension;
        $location = "img/" . $new_filename;
        $attempts++;
    }

    // If there are still conflicts after 10 attempts, use the current time
    if ($attempts >= 10) {
        $new_filename = $base_name . time() . "." . $extension;
        $location = "img/" . $new_filename;
    }

    return $location;
}
?>