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

//if edit
if ($_POST['change_pic'] == 1) { //img not changed
	//do
	//sqlinjection
	$sql = "UPDATE device_type SET device_type_indicator =  '$device_type_indicator', device_type_name =  '$device_type_name', max_loan_days =  '$max_loan_days', home_department = '$device_home_department'";
	if ($device_info != "") $sql .= ", device_type_info = '$device_info'";
	else $sql .= ", device_type_info = null";
	
	if ($device_type_storage != "") $sql .= ", device_type_storage = '$device_type_storage'";
	else $sql .= ", device_type_storage = null";
	
	if ($device_tooltip != "") $sql .= ", tooltip = '$device_tooltip'";
	else $sql .= ", tooltip = null";
	
	$sql .= " WHERE device_type_id = " . $_POST['old_type'];
	if (mysqli_query($link, $sql)) {
		//update department
	} else {
		$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
	}
	echo "<script type='text/javascript'>location.href = 'edit_type.php?reason=edit&type=" . $device_type . "';</script>";
} else if ($_POST['change_pic'] == 2) { //update pic
	if ($_FILES['file']['name']) {
		$filename = $_FILES['file']['name'];
		$location = "img/" . $filename;

		if (file_exists($location)) {
			$location = get_new_name($filename);
		}

		if (!file_exists($location)) {
			if (copy($_FILES['file']['tmp_name'], $location)) {
				if ($_POST['device_type_name']) {
					//sqlinjection
					$sql = "UPDATE device_type SET device_type_indicator =  '$device_type_indicator', device_type_name =  '$device_type_name', home_department = '$device_home_department', device_type_img_path = '$location'";
					if ($device_info != "") $sql .= ", device_type_info = '$device_info'";
					else {
						$sql .= ", device_type_info = null";
					}
					if ($device_tooltip != "") $sql .= ", tooltip = '$device_tooltip'";
					else {
						$sql .= ", tooltip = null";
					}
					$sql .= " WHERE device_type_id = " . $_POST['old_type'];

					if (mysqli_query($link, $sql)) {
						//update department
						echo "<script type='text/javascript'>location.href = 'edit_type.php?reason=edit&type=" . $device_type . "';</script>";
					} else {
						$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
						error_to_superadmin(get_superadmins(), $mail, $error);
					}
				}
			} else {
				$errors = error_get_last();
				error_to_superadmin(get_superadmins(), $mail, "ERROR: in 145 simple-upload: " . $errors['type'] . " " . $errors['message']);
			}
		} else {
			$error = "ERROR: file with same name exists: " . $filename;
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}
} else { //if create
	if ($_FILES['file']['name']) { //with pic
		$filename = $_FILES['file']['name'];
		$location = "img/" . $filename;

		if (file_exists($location)) {
			$location = get_new_name($filename);
		}

		if (!file_exists($location)) {
			if (copy($_FILES['file']['tmp_name'], $location)) { //check for doubles
				if ($_POST['device_type_name']) {
					//sqlinjection
					$sql = "INSERT INTO device_type (device_type_indicator, device_type_name, device_type_info, device_type_img_path, tooltip, home_department, max_loan_days) VALUES ('$device_type_indicator','$device_type_name','$device_info','$location','$device_tooltip', '$device_department', '$max_loan_days')";
					if (mysqli_query($link, $sql)) {
						//set department
						//sqlinjection
						$sql = "INSERT INTO type_department (type_id, department_id) VALUES ('" . mysqli_insert_id($link) . "','$device_department')";
						if (mysqli_query($link, $sql)) {
							echo "<script type='text/javascript'>location.href = 'edit_department.php?reason=create&depart=" . $device_department . "';</script>";
						} else {
							$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
							error_to_superadmin(get_superadmins(), $mail, $error);
						}
					} else {
						$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
						error_to_superadmin(get_superadmins(), $mail, $error);
					}
				}
			} else {
				$errors = error_get_last();
				error_to_superadmin(get_superadmins(), $mail, "ERROR: in 145 simple-upload: " . $errors['type'] . " " . $errors['message']);
			}
		} else {
			$error = "ERROR: file with same name exists: " . $filename;
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	} else {
		if ($_POST['device_type_name']) {
			//sqlinjection
			$sql = "INSERT INTO device_type (device_type_indicator, device_type_name, device_type_info, tooltip, home_department, max_loan_days) VALUES ('$device_type_indicator','$device_type_name','$device_info','$device_tooltip', '$device_department', '$max_loan_days')";
			if (mysqli_query($link, $sql)) {
				//set department
				//sqlinjection
				$sql = "INSERT INTO type_department (type_id, department_id) VALUES ('" . mysqli_insert_id($link) . "','$device_department')";
				if (mysqli_query($link, $sql)) {
					echo "<script type='text/javascript'>location.href = 'edit_department.php?reason=create&depart=" . $device_department . "';</script>";
				} else {
					$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
					error_to_superadmin(get_superadmins(), $mail, $error);
				}
			} else {
				$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
				error_to_superadmin(get_superadmins(), $mail, $error);
			}
		}
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