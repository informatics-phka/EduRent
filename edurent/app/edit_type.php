<!DOCTYPE HTML>
<?php

if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

$is_superadmin = is_superadmin($user_username);

//check if all infos are set
if (isEmpty($_GET['type'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_type.php: _GET[type] isEmpty {" . $_GET['type'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

//get data
$departments = get_departmentnames();
$limits = get_limits_of("device_type");

$part_of_department = [];
$query = "SELECT department_id FROM type_department WHERE type_id = ?";
if ($stmt = mysqli_prepare($link, $query)) {
	mysqli_stmt_bind_param($stmt, "i", $_GET['type']);

	if (mysqli_stmt_execute($stmt)) {
		mysqli_stmt_store_result($stmt);
		if (mysqli_stmt_num_rows($stmt) > 0) {
			mysqli_stmt_bind_result($stmt, $department_id);
			while (mysqli_stmt_fetch($stmt)) {
				$part_of_department[] = $department_id;
			}
		} else {
			save_in_logs("ERROR: No data found (" . $query . ") 52");
		}
	} else {
		save_in_logs("ERROR: " . mysqli_error($link));
		save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
	}
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

$type = [];
$sql = "SELECT * FROM device_type";
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
            $type[$row['device_type_id']] = [
                'device_type_name' => $row['device_type_name'],
                'device_type_indicator' => $row['device_type_indicator'],
                'device_type_info' => $row['device_type_info'],
                'device_type_img_path' => $row['device_type_img_path'],
                'device_type_storage' => $row['device_type_storage'],
                'device_tooltip' => $row['tooltip'],
                'home_department' => $row['home_department'],
                'max_loan_days' => $row['max_loan_days'],
            ];
        }
		mysqli_free_result($result);
	} else {
        save_in_logs("ERROR: No data found (" . $sql . ")");
    }
} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

//check if type exists
if(!$type[$_GET['type']]['device_type_name']){
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_type.php: type existiert nicht");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

//check if is admin of department from the device type
$device_department= $type[$_GET['type']]['home_department'];
check_is_admin_of_department($user_username, $device_department);

if (count($part_of_department) == 0) $part_of_department[0] = $unassigned_institute;

// edit device
if (exists_and_not_empty('reason', $_POST)) {
    $device_serialnumber = $_POST['serialnumber'];
    $device_type_id = $_POST['type'];
    $device_tag = $_POST['tag'];
    $device_blocked = $_POST['blocked'];
    $device_id = $_POST['id'];
    $device_note = $_POST['note'];

    $sql = "UPDATE device_list SET serialnumber = ?, device_type_id = ?, device_tag = ?, blocked = ?, note = ? WHERE device_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param(
            $stmt, 
            "sisisi",
            $device_serialnumber,
            $device_type_id,
            $device_tag,
            $device_blocked,
            $device_note,
            $device_id
        );

        if (mysqli_stmt_execute($stmt)) {
			$text = "INFO: Das Gerät '" . $type[$device_type_id]['device_type_indicator'] . $device_tag . "' wurde bearbeitet";
			save_in_logs($text, $user_firstname, $user_lastname, false);

			$SESSION->toasttext = $text;
			session_write_close();
			echo "<script>window.location.href = 'edit_type.php?type=" . htmlspecialchars($_POST['type'], ENT_QUOTES, 'UTF-8') . "';</script>";
        } else {
            $error = "ERROR: Could not able to execute statement: " . mysqli_stmt_error($stmt);
            error_to_superadmin(get_superadmins(), $mail, $error);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "ERROR: Could not prepare statement: " . mysqli_error($link);
        error_to_superadmin(get_superadmins(), $mail, $error);
    }
}

if(exists_and_not_empty('reason', $_GET)){ //type
	if (mysqli_query($link, $sql)) {
		$text = "INFO: Das Gerät '" . $type[$_GET['type']]['device_type_name'] . $_GET['type'] . "' wurde bearbeitet";
		save_in_logs($text, $user_firstname, $user_lastname, false);

		$SESSION->toasttext = $text;
		session_write_close();
		echo "<script>window.location.href = 'edit_type.php?type=" . $_GET['type'] . "';</script>";
	} else {
		$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
		error_to_superadmin(get_superadmins(), $mail, $error);
	}
}

// Create a device
if (exists_and_not_empty('create', $_POST)) {
    $device_type_id_id = $_POST['id'];
    $device_tag = $_POST['device_tag'];
    $serialnumber = $_POST['serialnumber'];
    $blocked = $_POST['blocked'];

    $sql = "INSERT INTO device_list (device_type_id, device_tag, serialnumber, blocked) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // Annahme: device_type_id_id und blocked sind Integer, device_tag und serialnumber Strings
        mysqli_stmt_bind_param($stmt, "isss", $device_type_id_id, $device_tag, $serialnumber, $blocked);
        if (mysqli_stmt_execute($stmt)) {
            $text = "INFO: Das Gerät '" . $type[$device_type_id_id]['device_type_indicator'] . $device_tag . "' wurde erfolgreich erstellt";
            save_in_logs($text, $user_firstname, $user_lastname);

            $SESSION->toasttext = $text;
			session_write_close();
            echo "<script>window.location.href = 'edit_type.php?type=" . htmlspecialchars($_GET['type']) . "';</script>";
        } else {
            $error = "ERROR: Could not execute statement: " . mysqli_error($link);
            error_to_superadmin(get_superadmins(), $mail, $error);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "ERROR: Could not prepare statement: " . mysqli_error($link);
        error_to_superadmin(get_superadmins(), $mail, $error);
    }
}

// Remove device
if (exists_and_not_empty('remove_id', $_GET)) {
    $device_id = $_GET['remove_id'];
    $selected_type_id = $_GET['selected_type_id'];
    $device_tag = $_GET['device_tag'];

    $sql = "DELETE FROM device_list WHERE device_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $device_id);
        if (mysqli_stmt_execute($stmt)) {
            $text = "INFO: Das Gerät '" . $type[$selected_type_id]['device_type_indicator'] . $device_tag . "' wurde erfolgreich gelöscht";
            save_in_logs($text, $user_firstname, $user_lastname);

            $SESSION->toasttext = $text;
			session_write_close();
            echo "<script>window.location.href = 'edit_type.php?type=" . htmlspecialchars($_GET['type']) . "';</script>";
        } else {
            $error = "ERROR: Could not execute statement: " . mysqli_error($link);
            error_to_superadmin(get_superadmins(), $mail, $error);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "ERROR: Could not prepare statement: " . mysqli_error($link);
        error_to_superadmin(get_superadmins(), $mail, $error);
    }
}

//device list
$devices = array();

$query = "SELECT device_tag, blocked FROM device_list WHERE device_type_id = ? ORDER BY device_tag";
if ($stmt = mysqli_prepare($link, $query)) {
	mysqli_stmt_bind_param($stmt, "i", $_GET['type']);

	if (mysqli_stmt_execute($stmt)) {
		mysqli_stmt_store_result($stmt);
		if (mysqli_stmt_num_rows($stmt) > 0) {
			mysqli_stmt_bind_result($stmt, $tag, $blocked);
			while (mysqli_stmt_fetch($stmt)) {
				$id = count($devices);
				$devices[$id]['tag'] = $tag;
				$devices[$id]['blocked'] = $blocked;
			}
		} else {
			save_in_logs("INFO: Keine Geräte gefunden (" . $query . ")");
		}
	} else {
		save_in_logs("ERROR: " . mysqli_error($link));
		save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
	}
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

$not_blocked_devices = 0;
$blocked_devices = 0;
$blocked_onsite = 0;
$blocked_update = 0;
$blocked_bug = 0;
$blocked_repair = 0;
$blocked_permanent = 0;

for ($i = 0; $i < count($devices); $i++) {
	if ($devices[array_keys($devices)[$i]]['blocked'] == 0) $not_blocked_devices++;
	else $blocked_devices ++;
	if ($devices[array_keys($devices)[$i]]['blocked'] == 1) $blocked_permanent++;
	else if ($devices[array_keys($devices)[$i]]['blocked'] == 2) $blocked_update++;
	else if ($devices[array_keys($devices)[$i]]['blocked'] == 3) $blocked_bug++;
	else if ($devices[array_keys($devices)[$i]]['blocked'] == 4) $blocked_repair++;
	else if ($devices[array_keys($devices)[$i]]['blocked'] == 5) $blocked_onsite++;
}

$reservated_devices = array();
$query = "SELECT DISTINCT device_list.device_tag FROM devices_of_reservations, reservations, device_list WHERE reservations.date_from <= DATE(NOW()) AND reservations.date_to >= DATE(NOW()) AND reservations.reservation_id = devices_of_reservations.reservation_id AND reservations.status != 4 AND reservations.status != 6 AND devices_of_reservations.device_id=device_list.device_id AND device_list.device_type_id = ?;";
if ($stmt = mysqli_prepare($link, $query)) {
	mysqli_stmt_bind_param($stmt, "i", $_GET['type']);

	if (mysqli_stmt_execute($stmt)) {
		mysqli_stmt_store_result($stmt);
		if (mysqli_stmt_num_rows($stmt) > 0) {
			mysqli_stmt_bind_result($stmt, $device_tag);
			while (mysqli_stmt_fetch($stmt)) {
				$reservated_devices[count($reservated_devices)] = $device_tag;
			}	
		}
	} else {
		save_in_logs("ERROR: " . mysqli_error($link));
		save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
	}
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

$devices_on_site = $not_blocked_devices - count($reservated_devices);
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
	<link rel="stylesheet" href="style-css/edit_type.css">
	
	<!-- html editor -->
	<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
	<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
	
	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

	<!-- Select2 -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	
	<!-- Toast -->
	<?php require_once("Controller/toast.php");?>
</head>
<body>
	<div class="main">
		<?php require_once 'navbar.php'; ?>
		<br>
		<script>
			var type_array = <?php echo json_encode($type); ?>;
			var old_type = <?php echo $_GET['type']; ?>;

			var old_indicator = type_array[old_type]['device_type_indicator'];
			var old_name = type_array[old_type]['device_type_name'];

			function isUnic(value, type) {
				keys = Object.keys(type_array);
				if (type == "name") {
					if (value == old_name) { //not changed
						return true;
					}
					for (let i = 0; i < Object.keys(type_array).length; i++) {
						if (type_array[keys[i]]['device_type_name'] == value) {
							return false
						}
					}
					return true
				}
				if (type == "indicator") {
					if (value == old_indicator) { //not changed
						return true;
					}
					for (let i = 0; i < Object.keys(type_array).length; i++) {
						if (type_array[keys[i]]['device_type_indicator'] == value) {
							return false
						}
					}
					return true
				}
			}

			function triggerImageUpload() {
				document.getElementById('device_img').click();
			}

			function previewImage(event) {
				const file = event.target.files[0];
				if (!file) return;

				const reader = new FileReader();
				reader.onload = function (e) {
					const preview = document.getElementById('imagePreview');
					if (preview.tagName.toLowerCase() === 'img') {
						preview.src = e.target.result;
					} else {
						const img = document.createElement('img');
						img.src = e.target.result;
						img.className = 'zoomD';
						img.id = 'imagePreview';

						const wrapper = document.getElementById('imgWrapper');
						wrapper.replaceChild(img, preview);
					}
					document.getElementById('delete_image').value = '0';
				};
				reader.readAsDataURL(file);
			}

			function deleteImage() {
				if (!confirm("Möchtest du das Bild wirklich löschen?")) return;

				const wrapper = document.getElementById('imgWrapper');
				const current = document.getElementById('imagePreview');
				if (current) wrapper.removeChild(current);

				const icon = document.createElement('i');
				icon.className = 'fa-solid fa-circle-question placeholder-icon';
				icon.id = 'imagePreview';
				wrapper.insertBefore(icon, wrapper.querySelector('.edit-icon'));

				document.getElementById('delete_image').value = '1';
				document.getElementById('device_img').value = '';
			}
		</script>
		<h3><?php echo translate('word_type'); ?> '<?php echo $type[$_GET['type']]['device_type_name']; ?>' <?php echo translate('word_edit'); ?></h3>
		<form id="myForm" name="myForm" action="simple_upload.php" method="post" enctype="multipart/form-data">
			<input style="display:none;" class="form-control" type="text" id="device_type_id" name="device_type_id">

			<div style="display: flex; align-items: flex-start; gap: 20px; margin-top: 10px;">
				<div class="img-wrapper" id="imgWrapper">
					<?php if (!empty($type[$_GET['type']]['device_type_img_path'])): ?>
						<img src="<?= $type[$_GET['type']]['device_type_img_path'] ?>" alt="Bild des Typs" class="zoomD" id="imagePreview">
					<?php else: ?>
						<i class="fa-solid fa-circle-question placeholder-icon" id="imagePreview"></i>
					<?php endif; ?>

					<!-- Bearbeiten -->
					<div class="edit-icon" title="Bild ändern" onclick="triggerImageUpload();">
						<i class="fas fa-pen"></i>
					</div>

					<!-- Löschen -->
					<div class="delete-icon" title="Bild löschen" onclick="event.stopPropagation(); deleteImage();">
						<i class="fas fa-trash"></i>
					</div>
				</div>
			</div>

			<input type="file" name="file" id="device_img" accept="image/*" style="display:none;" onchange="previewImage(event)">
			<input type="hidden" name="delete_image" id="delete_image" value="0">
			<br>

			<div class="input-control">
				<label for="device_type_name"><?php echo translate('text_deviceTypeName'); ?></label>
				<input class="form-control rounded" id="device_type_name" name="device_type_name" type="text" placeholder="iPad" maxlength='<?php echo $limits['device_type_name']; ?>'>

				<div class="error error_text"></div>
			</div>

			<div class="input-control">
				<label for="device_type_indicator"><?php echo translate('text_deviceTypeIndicator'); ?></label>
				<input class="form-control rounded" id="device_type_indicator" name="device_type_indicator" type="text" placeholder="ii" maxlength='<?php echo $limits['device_type_indicator']; ?>'>

				<div class="error error_text"></div>
			</div>

			<div class="input-control">
				<label for="max_loan_days"><?php echo translate('text_deviceTypeMaxLoan'); ?></label>
				<input class="form-control rounded" id="max_loan_days" name="max_loan_days" type="text" placeholder="14" maxlength='<?php echo $limits['max_loan_days']; ?>'>

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

			<!-- Department Select -->
			<label for='home_department'><?php echo translate('text_homeDepartment'); ?></label>
			<select class='form-select' name='home_department' id='home_department'>
				<?php
				for ($i = 0; $i < count($departments); $i++) {
					if (array_keys($departments)[$i] == $all_institutes) continue;
					if (array_keys($departments)[$i] == $unassigned_institute) continue;
					if (array_keys($departments)[$i] == $type[$_GET['type']]['home_department']) echo "<option selected value='" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]][get_language()] . "</option>";
					else echo "<option value='" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]][get_language()] . "</option>";
				}
				?>
			</select>
			<br>

			<label for="storage_info"><?php echo translate('word_storageInfo'); ?></label>
			<input class="form-control rounded" type="text" id="storage_info" name="storage_info" placeholder="Im Schrank 4, drittes Fach von unten" maxlength='<?php echo $limits['device_type_storage']; ?>'>
			<br>


			<?php
            echo "Ausleihbar für:";
                //List of departments starting with all departments and no departments and listing all other departments				      		
			?>

			<select class="form-control js-example-basic-multiple"
					id="department_select"
					name="departments[]"
					multiple="multiple"
					required>

				<?php
					$main_options = [0, -1]; // Alle Institute / Kein Institut
					foreach ($main_options as $opt) {
						$isSelected = in_array((string)$opt, $part_of_department) ? 'selected' : '';
						$label = (get_language() == "de") ? $departments[$opt]['de'] : $departments[$opt]['en'];
						echo "<option value='{$opt}' {$isSelected}>{$label}</option>";
					}

					foreach ($departments as $key => $value) {
						if ($key == $unassigned_institute || $key == $all_institutes) continue;
						$isSelected = in_array((string)$key, $part_of_department) ? 'selected' : '';
						$label = (get_language() == "de") ? $value['de'] : $value['en'];
						echo "<option value='{$key}' {$isSelected}>{$label}</option>";
					}
				?>
			</select>
			<script>
				document.addEventListener("DOMContentLoaded", function() {
					const $select = $('#department_select');
					const allValue = "0";
					const noneValue = "-1";

					// Initialize Select2
					$select.select2({
						placeholder: "Institut auswählen",
						width: '100%',
						closeOnSelect: false,
					});

					$select.on('change', function () {
						let selected = $(this).val() || [];

						// Case 1: "All" selected → keep only "All"
						if (selected.includes(allValue)) {
							$select.val([allValue]).trigger('change.select2');
							showToast('Es wurde „Alle Institute“ ausgewählt. Andere Optionen wurden entfernt.');
							return;
						}

						// Case 2: "Kein" selected → keep only "Kein"
						if (selected.includes(noneValue)) {
							$select.val([noneValue]).trigger('change.select2');
							showToast('Es wurde „Kein Institut“ ausgewählt. Andere Optionen wurden entfernt.');
							return;
						}
					});
				});
			</script>

			<br>
			<br>

			<!-- hidden values -->
			<input type="hidden" class="form-control" id="reason" name="reason" value="edit">
			<input type="hidden" class="form-control" id="old_type" name="old_type" value=<?php echo $_GET['type']; ?>>

			<!-- Buttons -->
			<div class='row justify-content-center'>
				<div class='col-md-6 mb-3'>
					<a class='btn btn-secondary btn-block' href='edit_department.php?depart=<?php echo $type[$_GET["type"]]["home_department"]; ?>'>
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
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<button type='button' class='btn btn-danger btn-block rounded mr-1 mb-1' onclick=window.location.href='edit_department.php?depart=<?php echo $type[$_GET["type"]]["home_department"]; ?>&remove_id=<?php echo $_GET["type"]; ?>'>
					<i class="fas fa-trash-alt mr-2"></i>
					<?php echo translate('word_delete'); ?>
				</button>
			</div>	
		</div>	
	</div>
	<br>
	<div>
		<h3 class="d-flex align-items-center gap-2">
			<?php echo translate('word_deviceList'); ?>
			<button class="btn bg-secondary text-white rounded-pill px-3 py-1 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#statusSidebar">
				<?php echo count($devices); ?>
			</button>
		</h3>

		<!-- Sidebar -->
		<div class="offcanvas offcanvas-end" tabindex="-1" id="statusSidebar">
			<div class="offcanvas-header">
				<h5 class="offcanvas-title">Statusübersicht</h5>
				<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
			</div>
			<div class="offcanvas-body d-flex flex-column">
				<div class="flex-grow-1 overflow-auto">
					<ul class="list-group list-group-flush mb-3">
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Verfügbar
							<span class="badge bg-success rounded-pill"><?php echo $devices_on_site; ?></span>
						</li>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Reserviert
							<span class="badge bg-warning rounded-pill"><?php echo count($reservated_devices); ?></span>
						</li>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Blockiert
							<span class="badge bg-danger rounded-pill"><?php echo $blocked_devices; ?></span>
						</li>
						<li class="list-group-item ms-3 d-flex justify-content-between align-items-center">
							<small>↳ vor Ort</small>
							<span class="badge bg-danger rounded-pill"><?php echo $blocked_onsite; ?></span>
						</li>
						<li class="list-group-item ms-3 d-flex justify-content-between align-items-center">
							<small>↳ braucht Update</small>
							<span class="badge bg-danger rounded-pill"><?php echo $blocked_update; ?></span>
						</li>
						<li class="list-group-item ms-3 d-flex justify-content-between align-items-center">
							<small>↳ dauerhaft belegt</small>
							<span class="badge bg-danger rounded-pill"><?php echo $blocked_permanent; ?></span>
						</li>
						<li class="list-group-item ms-3 d-flex justify-content-between align-items-center">
							<small>↳ Fehlfunktion</small>
							<span class="badge bg-danger rounded-pill"><?php echo $blocked_bug; ?></span>
						</li>
						<li class="list-group-item ms-3 d-flex justify-content-between align-items-center">
							<small>↳ Reparatur</small>
							<span class="badge bg-danger rounded-pill"><?php echo $blocked_repair; ?></span>
						</li>
					</ul>
				</div>
				<div class="border-top pt-2 small text-muted">
					<strong>Status-Legende:</strong><br>
					🟥 Blockiert<br>
					🟧 Reserviert<br>
					🟩 Verfügbar<br>
					⬛ Fehler
				</div>
			</div>
		</div>

		<!-- Add device -->
		<a href='add_device.php?type=<?php echo $_GET['type']; ?>'>
			<p style='border:2px; margin-bottom: 1px; border-radius: 10px; border-style:solid; border-color:#000000; padding-left: 1em;'><?php echo translate('word_add'); ?></p>
		</a>

		<!-- Search -->
		<input type="text" id="typeSearch" class="styled-search" placeholder="Eintrag suchen...">

		<!-- All devices -->
		<div id="typeLinks">
			<?php
			for ($i = 0; $i < count($devices); $i++) {
				$blocked = $devices[array_keys($devices)[$i]]['blocked'];
				$device_tag = $devices[array_keys($devices)[$i]]['tag'];
				$device_type_indicator = $type[$_GET['type']]['device_type_indicator'];
				$icon_class = "";

				if(in_array($device_tag,$reservated_devices)){ //is reservated
					if(in_array($device_tag,$reservated_devices) && $blocked != 0){ //is reservated and blocked
						$background_color = "#000000";
					}
					else{
						$background_color = "#C19410";
					}
				}
				else{					
					switch ($blocked) {
						case 0: //not blocked
							$background_color = "#6FB40F"; //green
							break;
						case 1:
							$background_color = "#DC0606"; //red
							break;
						case 2:
							$icon_class = "fa-cloud-arrow-down";
							$background_color = "#DC0606"; //red
							break;
						case 3:
							$icon_class = "fa-bug";
							$background_color = "#DC0606"; //red
							break;
						case 4:
							$icon_class = "fa-tools";
							$background_color = "#DC0606"; //red
							break;
						case 5:
							$icon_class = "fa-building";
							$background_color = "#DC0606"; //red
							break;
						default: //debug
							$icon_class = "fa-question-circle";
							$background_color = "#CCCCCC"; //grey
							break;
					}
				}

				echo "<a class='type' href='edit_device.php?type={$_GET['type']}&device={$device_tag}'>
					<div style='border: 2px solid #000000; margin-bottom: 1px; border-radius: 10px; background-color:{$background_color}; padding: 5px; display: flex; align-items: center;'>
						<i class='fas {$icon_class}' style='font-size: 20px; margin-right: 5px;'></i> <p style='margin-bottom: 0;'>{$device_type_indicator}{$device_tag}</p>
					</div>
				</a>";
			}
			?>
		</div>
	</div>
</div>
</body>
<script>
	//Search
	document.getElementById('typeSearch').addEventListener('input', function () {
		const query = this.value.toLowerCase();
		const links = document.querySelectorAll('#typeLinks .type');

		links.forEach(link => {
			const text = link.textContent.toLowerCase();
			link.style.display = text.includes(query) ? 'block' : 'none';
		});
	});

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

	//check name
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

	setSuccess(name);

	//check indicator
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

	//check max_loan_days
	const loan_days = document.getElementById('max_loan_days');
	const inputHandler3 = function(e) {
		var error;
		var max_span = <?php global $max_loan_duration; echo is_null($max_loan_duration) ? "2" : json_encode($max_loan_duration); ?>;
		if (!loan_days.value) error = "<?php echo translate('text_deviceTypeMaxLoanError'); ?>";
		if(only_numbers(loan_days.value)) error = "<?php echo translate('text_deviceTypeMaxLoanError'); ?>";
		if(loan_days.value > max_span) error = "Die maximale Ausleihdauer darf nicht größer als " + max_span + " sein.";
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

	var type = <?php echo is_null($type) ? "2" : json_encode($type); ?>;

	var selcted_id = <?php echo $_GET['type']; ?>;

	//set inputs
	document.getElementById("device_type_id").value = selcted_id;
	document.getElementById("device_type_name").value = type[selcted_id]['device_type_name'];
	document.getElementById("device_info").value = type[selcted_id]['device_type_info'];
	document.getElementById("device_type_indicator").value = type[selcted_id]['device_type_indicator'];
	if(type[selcted_id]['device_tooltip']) editor.root.innerHTML = type[selcted_id]['device_tooltip'];
	document.getElementById("storage_info").value = type[selcted_id]['device_type_storage'];
	document.getElementById("max_loan_days").value = type[selcted_id]['max_loan_days'];

	//checkbox controll
	var all = '<?php echo $all_institutes; ?>';
	var none = '<?php echo $unassigned_institute; ?>';

	$("#checks :checkbox").change(function(e) {
		if ($(this).is(":checked") && ($(this).attr("name").includes(none) || $(this).attr("name").includes(all))) {
			$('input:checkbox').not(this).prop('checked', false);
		} else {
			var search = 'input:checkbox[name*=' + none + ']';
			$(search).not(this).prop('checked', false);

			var search = 'input:checkbox[name*=' + all + ']';
			$(search).not(this).prop('checked', false);
		}
	});
</script>

<?php
echo $OUTPUT->footer();
?>