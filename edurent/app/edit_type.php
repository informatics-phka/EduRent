<!DOCTYPE HTML>
<?php

if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

//check if all infos are set
if (isEmpty($_GET['type'])) {
	error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_type.php: _GET[type] isEmpty {" . $_GET['type'] . "}");
	echo "<script>window.location.href = 'admini';</script>";
	exit;
}

//get limits of types
$limits = get_limits_of("device_type");
$type;
$sql = "SELECT * FROM device_type";
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$type[$row['device_type_id']]['device_type_name'] = $row['device_type_name'];
			$type[$row['device_type_id']]['device_type_indicator'] = $row['device_type_indicator'];
			$type[$row['device_type_id']]['device_type_info'] = $row['device_type_info'];
			$type[$row['device_type_id']]['device_type_img_path'] = $row['device_type_img_path'];
			$type[$row['device_type_id']]['device_type_storage'] = $row['device_type_storage'];
			$type[$row['device_type_id']]['device_tooltip'] = $row['tooltip'];
			$type[$row['device_type_id']]['home_department'] = $row['home_department'];
			$type[$row['device_type_id']]['max_loan_days'] = $row['max_loan_days'];
		}
		mysqli_free_result($result);
	}
} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

//get data
$departments = get_departmentnames();

$part_of_department = array();
$query = "SELECT department_id FROM type_department WHERE type_id = ?";
if ($stmt = mysqli_prepare($link, $query)) {
	mysqli_stmt_bind_param($stmt, "i", $_GET['type']);

	if (mysqli_stmt_execute($stmt)) {
		mysqli_stmt_store_result($stmt);
		if (mysqli_stmt_num_rows($stmt) > 0) {
			mysqli_stmt_bind_result($stmt, $department_id);
			while (mysqli_stmt_fetch($stmt)) {
				$part_of_department[count($part_of_department)] = $department_id;
			}
		} else {
			save_in_logs("ERROR: Kein Datensatz gefunden (" . $query . ") 52");
		}
	} else {
		save_in_logs("ERROR: " . mysqli_error($link));
		save_in_logs("ERROR: " . mysqli_stmt_error($stmt));
	}
} else {
	save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
}
$stmt->close();

if (count($part_of_department) == 0) $part_of_department[0] = $unassigned_institute;
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
		
		<!-- html editor -->
		<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
		<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
		
		<!-- Font Awesome -->
    	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    	
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
		<style>
			a,
			a:hover,
			a:focus,
			a:active {
				text-decoration: none;
				color: inherit;
			}

			/* clickable images */
			#lightbox {
				position: absolute;
				z-index: 999;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				width: 80vw;
				height: 80vh;
				display: flex;
				align-items: center;
				visibility: hidden;
				opacity: 0;
				transition: opacity ease 0.6s;
			}

			#lightbox.show {
				visibility: visible;
				opacity: 1;
			}

			#lightbox img {
				width: 100%;
				height: 100%;
				object-fit: contain;
				background: rgba(0, 0, 0, 0.5);
			}
		</style>
	</head>
	<?php
	$device_department= $type[$_GET['type']]['home_department'];
	check_is_admin_of_department($user_username, $device_department);

	if(exists_and_not_empty('reason', $_POST)){ //device save
		//sqlinjection
		$device_serialnumber = $_POST['serialnumber'];
		$device_type_id = $_POST['type'];
		$device_tag = $_POST['tag'];
		$device_blocked = $_POST['blocked'];
		$device_id = $_POST['id'];
		$device_note = $_POST['note'];
		$sql = "UPDATE device_list SET serialnumber='" . $device_serialnumber . "', device_type_id='" . $device_type_id . "', device_tag='" . $device_tag . "', blocked='" . $device_blocked . "', note='" . $device_note . "' WHERE device_id='" . $device_id . "'";
		if (mysqli_query($link, $sql)) {
			if ($_POST['reason'] == "edit") {
				$text = "Device '" . $type[$device_type_id]['device_type_indicator'] . $device_tag . "' wurde bearbeitet";
				save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
				 
				$SESSION->toasttext = $text;
				echo "<script>window.location.href = 'edit_type.php?type=" . $_POST['type'] . "';</script>";
			} else error_to_superadmin(get_superadmins(), $mail, "ERROR: in 149 edit_type: " . $_POST['reason']);
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if(exists_and_not_empty('reason', $_GET)){ //type
		if (mysqli_query($link, $sql)) {
			$text = "Devicetyp '" . $type[$_GET['type']]['device_type_name'] . $_GET['type'] . "' wurde bearbeitet";
			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);

			$SESSION->toasttext = $text;
			echo "<script>window.location.href = 'edit_type.php?type=" . $_GET['type'] . "';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	//create a device
	if(exists_and_not_empty('create', $_POST)){
		//sqlinjection
		$device_type_id_id = $_POST['id'];
		$device_tag = $_POST['device_tag'];
		$serialnumber = $_POST['serialnumber'];
		$blocked = $_POST['blocked'];
		$sql = "INSERT INTO device_list (device_type_id, device_tag, serialnumber, blocked) VALUES ('$device_type_id_id','$device_tag','$serialnumber','$blocked')";
		if (mysqli_query($link, $sql)) {
			$text = "INFO: Devicetag '" . $type[$device_type_id_id]['device_type_indicator'] . $device_tag . "' wurde erfolgreich erstellt";
			save_in_logs($text, $user_firstname, $user_lastname);

			$SESSION->toasttext = $text;
			echo "<script>window.location.href = 'edit_type.php?type=" . $_GET['type'] . "';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	//remove device
	if(exists_and_not_empty('remove_id', $_GET)){
		//sqlinjection
		$device_id = $_GET['remove_id'];
		$selected_type_id = $_GET['selected_type_id'];
		$device_tag = $_GET['device_tag'];
		$sql = "DELETE FROM device_list WHERE device_id='" . $device_id . "'";
		if (mysqli_query($link, $sql)) {
			$text = "INFO: Devicetag '" . $type[$selected_type_id]['device_type_indicator'] . $device_tag . "' wurde erfolgreich gelöscht";
			save_in_logs($text, $user_firstname, $user_lastname);
	
			$SESSION->toasttext = $text;
			echo "<script>window.location.href = 'edit_type.php?type=" . $_GET['type'] . "';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}
	if(!$type[$_GET['type']]['device_type_name']){
		error_to_superadmin(get_superadmins(), $mail, "ERROR: Fehler beim Aufrufen von edit_type.php: type existiert nicht");
		echo "<script>window.location.href = 'admini';</script>";
		exit;
	}
	?>
	<div class="main">
		<div id='lightbox'></div>
		<script>
			window.onload = () => { //zoom to click
				// GET LIGHTBOX & ALL .ZOOMD IMAGES
				let all = document.getElementsByClassName("zoomD"),
					lightbox = document.getElementById("lightbox");

				// CLICK TO SHOW IMAGE IN LIGHTBOX
				if (all.length > 0) {
					for (let i of all) {
						i.onclick = () => {
							let clone = i.cloneNode();
							clone.className = "";
							lightbox.innerHTML = "";
							lightbox.appendChild(clone);
							lightbox.className = "show";
						};
					}
				}

				// CLICK TO CLOSE LIGHTBOX
				lightbox.onclick = () => {
					lightbox.className = "";
				};
			};

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
		</script>
		<h3><?php echo translate('word_type'); ?> '<?php echo $type[$_GET['type']]['device_type_name']; ?>' <?php echo translate('word_edit'); ?></h3>
		<form id="myForm" name="myForm" action="../Controller/simple_upload.php" method="post" enctype="multipart/form-data">
			<input style="display:none;" class="form-control" type="text" id="device_type_id" name="device_type_id">

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
            
            <div id="checks">
				<?php
                $main_options = [0,-1];
                for ($i = 0; $i < count($main_options); $i++) {
                    echo "<div class='form-check form-switch'>";
                    if (in_array($main_options[$i], $part_of_department)) echo "<input class='form-check-input' type='checkbox' role='switch' checked name='switch_" . $main_options[$i] . "'>";
                    else echo "<input class='form-check-input' type='checkbox' role='switch' name='switch_" . $main_options[$i] . "'>";

                    if (get_language() == "de") echo "<label class='form-check-label' for='switch_" . $main_options[$i] . "'>" . $departments[$main_options[$i]]['de'] . "</label>";
                    else echo "<label class='form-check-label' for='switch_" . $main_options[$i] . "'>" . $departments[$main_options[$i]]['en'] . "</label>";
                    echo "</div>";
                }


				for ($i = 0; $i < count($departments); $i++) {
					if (array_keys($departments)[$i] == $unassigned_institute || array_keys($departments)[$i] == $all_institutes) continue;

					echo "<div class='form-check form-switch'>";
					if (in_array(array_keys($departments)[$i], $part_of_department)) echo "<input class='form-check-input' type='checkbox' role='switch' checked name='switch_" . array_keys($departments)[$i] . "'>";
					else echo "<input class='form-check-input' type='checkbox' role='switch' name='switch_" . array_keys($departments)[$i] . "'>";

					if (get_language() == "de") echo "<label class='form-check-label' for='switch_" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['de'] . "</label>";
					else echo "<label class='form-check-label' for='switch_" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]]['en'] . "</label>";
					echo "</div>";
				}
				?>
			</div>
			<br>

			<?php echo translate('word_deviceTypeImg'); ?>
			<?php
			//img vorhanden
			if ($type[$_GET['type']]['device_type_img_path'] != "") {
				echo "<div id='old_img' class='text-center'>";
				echo "<img src='" . $type[$_GET['type']]['device_type_img_path'] . "' alt='Bild des Typs' style='width:128px; heigth:auto; margin:5px 5px 5px 5px'; class='zoomD'>";
				echo "</div>";
			}
			?>
			<div class="form-check">
				<input class="form-check-input" type="radio" name="change_pic" id="change_pic" value=1 checked>
				<label class="form-check-label" for="change_pic">
					<?php echo translate('text_oldPic'); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input" type="radio" name="change_pic" id="change_pic" value=2>
				<label class="form-check-label" for="change_pic">
					<?php echo translate('text_newPic'); ?>
				</label>
			</div>
			<br>

			<div id="input_img" style="display:none;" class="mb-3">
				<input class="form-control rounded" id=device_img type="file" name="file" max-size="10" accept="image/png, image/jpeg, image/img">
			</div>
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
	<?php
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
	for ($i = 0; $i < count($devices); $i++) {
		if ($devices[array_keys($devices)[$i]]['blocked'] == 0) $not_blocked_devices++;
		else $blocked_devices ++;
	}

	$reservated_devices = array();
	$query = "SELECT DISTINCT device_list.device_tag FROM devices_of_reservations, reservations, device_list WHERE reservations.date_from <= DATE(NOW()) AND reservations.date_to >= DATE(NOW()) AND reservations.reservation_id = devices_of_reservations.reservation_id AND reservations.status = 3 AND devices_of_reservations.device_id=device_list.device_id AND device_list.device_type_id = ?;";
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
	<div>
		<h3><?php echo translate('word_deviceList'); ?></h3>

		<!-- Devicelist -->
		<div class='row no-gutters text-center'>
			<div class='col'>
				<?php echo translate('text_availableDevices') . ": " . count($devices); ?>
			</div>
			<div class='col'>
				<?php echo translate('text_blockedDevices') . ": " . $blocked_devices; ?>
			</div>
			<div class='col'>
				<?php echo translate('text_thereDevices') . ": " . $devices_on_site; ?>
			</div>
		</div>

		<!-- Add device -->
		<a href='add_device.php?type=<?php echo $_GET['type']; ?>'>
			<p style='border:2px; margin-bottom: 1px; border-radius: 10px; border-style:solid; border-color:#000000; padding-left: 1em;'><?php echo translate('word_add'); ?></p>
		</a>

		<!-- All devices -->
		<?php
		for ($i = 0; $i < count($devices); $i++) {
			if(in_array($devices[array_keys($devices)[$i]]['tag'],$reservated_devices)){ //is reservated
				if(in_array($devices[array_keys($devices)[$i]]['tag'],$reservated_devices) && $devices[array_keys($devices)[$i]]['blocked'] != 0){ //is reservated and blocked
					echo "<a href='edit_device.php?type=" . $_GET['type'] . "&device=" . $devices[array_keys($devices)[$i]]['tag'] . "'><p style='border:2px; margin-bottom: 1px; border-radius: 10px; border-style:solid; border-color:#000000; background-color:#000000; padding-left: 1em;'>" . $type[$_GET['type']]['device_type_indicator'] . $devices[array_keys($devices)[$i]]['tag'] . "</p></a>";
				}
				else{
					echo "<a href='edit_device.php?type=" . $_GET['type'] . "&device=" . $devices[array_keys($devices)[$i]]['tag'] . "'><p style='border:2px; margin-bottom: 1px; border-radius: 10px; border-style:solid; border-color:#000000; background-color:#C19410; padding-left: 1em;'>" . $type[$_GET['type']]['device_type_indicator'] . $devices[array_keys($devices)[$i]]['tag'] . "</p></a>";
				}
			}
			else{
				$status = $devices[array_keys($devices)[$i]]['blocked'];
				$device_tag = $devices[array_keys($devices)[$i]]['tag'];
				$device_type_indicator = $type[$_GET['type']]['device_type_indicator'];
				$icon_class = "";
				
				switch ($status) {
					case 0:
						$background_color = "#6FB40F";
						break;
					case 1:
						$background_color = "#DC0606";
						break;
					case 2:
						$icon_class = "fa-cloud-arrow-down";
						$background_color = "#DC0606";
						break;
					case 3:
						$icon_class = "fa-bug";
						$background_color = "#DC0606";
						break;
					case 4:
						$icon_class = "fa-tools";
						$background_color = "#DC0606";
						break;
					case 5:
						$icon_class = "fa-building";
						$background_color = "#DC0606";
						break;
					default:
						$icon_class = "fa-question-circle";
						$background_color = "#CCCCCC";
						break;
				}

				echo "<a href='edit_device.php?type={$_GET['type']}&device={$device_tag}'>
						<div style='border: 2px solid #000000; margin-bottom: 1px; border-radius: 10px; background-color:{$background_color}; padding: 5px; display: flex; align-items: center;'>
							<i class='fas {$icon_class}' style='font-size: 20px; margin-right: 5px;'></i> <p style='margin-bottom: 0;'>{$device_type_indicator}{$device_tag}</p>
						</div>
					</a>";
			}
		}
		?>
	</div>
	</div>
</body>
<?php require_once("Controller/move_to_top.php"); ?>
<script>
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

	//is radiobutton selected?
	var rad = document.myForm.change_pic;
	var prev = null;
	for (var i = 0; i < rad.length; i++) {
		rad[i].addEventListener('change', function() {
			if (this !== prev) {
				prev = this;
			}
			if (this.value == 2) {
				document.getElementById('input_img').style.display = 'block';
				document.getElementById('old_img').style.display = 'none';
			} else {
				document.getElementById('input_img').style.display = 'none';
				document.getElementById('old_img').style.display = 'block';
			}
		});
	}

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