<!DOCTYPE HTML>
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_is_admin($user_username);

//get data
$departments;
$sql = "SELECT * FROM departments";
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$departments[$row['department_id']]['mail'] = $row['mail'];
			$departments[$row['department_id']]['de'] = $row['department_de'];
			$departments[$row['department_id']]['en'] = $row['department_en'];
		}
		mysqli_free_result($result);
	}
} else error_to_superadmin(get_blocked_devices(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));

$pickupdays = get_allopeningdays();
$admins = get_all_admins();
$department_ids = get_admin_department($user_username);

//get orders you are allowed to see
$orders = array();
if (is_superadmin($user_username)) {
    $orders = get_all_orders();
} else {
	foreach ($department_ids as $department_id) {
        $department_orders = get_all_orders_from_department($department_id);
        if ($department_orders) {
            $orders = $orders + $department_orders;
        }
    }
}


//getlimits
$limits = get_limits_of("device_list");

$devices_of_deparment = array();;
$sql;
if(is_superadmin($user_username)){
	$sql = "SELECT device_type_name, device_type_indicator FROM departments, device_type WHERE department_id=home_department ORDER BY home_department";
}
else{
	$ids;
	for ($i=0; $i < count($department_ids); $i++) {
		if($i == 0) $ids = "(department_id=" . $department_ids[$i];
		else $ids .= " OR department_id=" . $department_ids[$i];
	}
	$ids .= ")"; 
	$sql = "SELECT device_type_name, device_type_indicator FROM departments, device_type WHERE department_id=home_department AND " . $ids . " ORDER BY home_department";
}
if ($result = mysqli_query($link, $sql)) {
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$id = count($devices_of_deparment);
			$devices_of_deparment[$id]['device_type_name'] = $row['device_type_name'];
			$devices_of_deparment[$id]['device_type_indicator'] = $row['device_type_indicator'];
		}
		mysqli_free_result($result);
	}
} else error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
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
		<link rel="stylesheet" href="style-css/page_colors.scss">
        <link rel="stylesheet" href="style-css/accessability.css">
		
		<!-- searchbar -->
		<script type="text/javascript" src="js/searchbar.js"></script>
		
		<!-- Font Awesome -->
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
		
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
	</head>
	<style>
		.collapsed {
			display: none;
		}
	</style>
	<script>
		function modal_load(reservation_id, from, to, fn, ln, status, room_from, room_to, department, time_from, time_to) {
			var mymodal = $('#my_modal');
			var titel = "<?php echo translate('word_order'); ?> #" + reservation_id;
			mymodal.find('.modal-title').text(titel);

			var orders = <?php echo is_null($orders) ? "2" : json_encode($orders); ?>;

			$("#button_grey").show();
			$("#button_green").show();
			$("#button_yellow").show();
			$("#button_grey").html('<?php echo translate('word_back'); ?>');
			$("#button_red").hide();
			$("#button_yellow").html('<?php echo translate('word_editBig'); ?>');
			$("#button_yellow").attr("onclick", "order_extend(" + reservation_id + ", '" + from + "', '" + to + "', '" + time_from + "', '" + time_to + "', '" + room_from + "', '" + room_to + "')");

			if (orders == "2" || orders[reservation_id] == null) { //Error
				var user = "<?php echo translate('word_from'); ?>: " + fn + " " + ln + "<br>";
				var department = "<?php echo translate('word_department'); ?>: " + department + "<br>";
				var translate_pickup = "<?php echo translate('text_pickupReservation'); ?>";
                var translate_return = "<?php echo translate('text_returnReservation'); ?>";
                translate_pickup = translate(translate_pickup, [from, time_from, room_from]);
                translate_return = translate(translate_return, [to, time_to, room_to]);

				var error = '<?php echo translate('text_noDevices'); ?> <br><br>' ;

				var string = '<center>' + error + user + department + translate_pickup + translate_return + '</center>';
				$("#button_red").hide();
				$("#button_yellow").hide();
				$("#button_green").html('<?php echo translate('word_delete'); ?>');
				$("#button_green").attr("onclick", "order_remove(" + reservation_id + ")");
			} else {
				var time;
				if (status == 2){ //Collectable
					$("#button_red").html('<?php echo translate('status_3'); ?>');
					$("#button_red").show();
					$("#button_red").attr("onclick", "order_pickup(" + reservation_id + ")");
					$("#button_green").html('<?php echo translate('word_cancel'); ?>');
					$("#button_green").attr("onclick", "order_cancel(" + reservation_id + ")");
					var translate_pickup = "<?php echo translate('text_pickupReservation'); ?>";
					var translate_return = "<?php echo translate('text_returnReservation'); ?>";
					time = translate(translate_pickup, [from, time_from, room_from]) + "<br>";
					time += translate(translate_return, [to, time_to, room_to]) + "<br>";
				} else if (status == 3){ //Retrieved
					$("#button_green").html('<?php echo translate('word_downgrade'); ?>');
					$("#button_green").attr("onclick", "order_back(" + reservation_id + ")");
					$("#button_red").html('<?php echo translate('word_return'); ?>');
					$("#button_red").attr("onclick", "order_retour(" + reservation_id + ")");
					$("#button_red").show();
					var translate_return = "<?php echo translate('text_returnReservation'); ?>";
					time = translate(translate_return, [to, time_to, room_to]) + "<br>";
				} else if (status == 1){ //request
					$("#button_red").html('<?php echo translate('word_confirm'); ?>');
					$("#button_red").show();
					$("#button_red").attr("onclick", "order_accept(" + reservation_id + ")");
					$("#button_green").html('<?php echo translate('word_cancel'); ?>');
					$("#button_green").attr("onclick", "order_cancel(" + reservation_id + ")");
					time = "<?php echo translate('word_period'); ?>: " + from + " <?php echo translate('word_to'); ?> " + to + "<br>";
					time += "<?php echo translate('word_pickupRoom'); ?>: " + room_from + ", <?php echo translate('word_returnRoom'); ?>: " + room_to + "<br>";
				} else if (status == 4 || status == 6){ //Completed or Cancelled
					$("#button_green").html('<?php echo translate('word_delete'); ?>');
					$("#button_green").attr("onclick", "order_remove(" + reservation_id + ")");
					$("#button_yellow").hide();
					time = "<?php echo translate('word_period'); ?>: " + from + " <?php echo translate('word_to'); ?> " + to + "<br>";
				}

				var d_ids = orders[reservation_id][0].split('|');
				var names = orders[reservation_id][1].split('|');
				var geraete = "<?php echo translate('word_devices'); ?>:<br>";
				for (var i = 0; i < d_ids.length; i++) {
					geraete += d_ids[i] + ", " + names[i] + "<br>";
				}

				var user = "<?php echo translate('word_from'); ?>: " + fn + " " + ln + "<br>";
				var department = "<?php echo translate('word_department'); ?>: " + department + "<br>";

				var string = '<center>' + user + department + time + '<br>' + geraete + '</center>';
			}
			mymodal.find('.modal-body').html(string);
			mymodal.modal('show');
		}
	</script>
	<?php

	setlocale(LC_ALL, "de_DE.utf8");

	$heute_datum = new DateTime();
	$heute_timestamp = date('Y-m-d', $heute_datum->getTimestamp());

	if(exists_and_not_empty('org', $_GET)){ //was fetched
		/** timestamp to collection period **/
		$reservation_id = $_GET['org'];

		/** get the infos from reservation **/
		$query = mysqli_query($link, "SELECT DISTINCT email, date_from, reservations.department_id, room_from FROM user, reservations, departments WHERE departments.department_id=reservations.department_id AND user.id=user_id AND reservation_id=" . $reservation_id);
		$row = mysqli_fetch_array($query);

		$abholbar_timestamp = strtotime($row['date_from']);
		$abholbar_wochentag = date("N", $abholbar_timestamp) % 7; //7 = 0
		$abholbar_uhrzeit;

		$days = array_keys($pickupdays[$row['department_id']]);
		for ($i = 0; $i < count($days); $i++) {
			if ($abholbar_wochentag == $pickupdays[$row['department_id']][$days[$i]]['dayofweek']) {
				$abholbar_uhrzeit = $pickupdays[$row['department_id']][$days[$i]]['time'];
				break;
			}
		}

		//sqlinjection
		$sql = "UPDATE reservations SET status='2' WHERE reservation_id=" . $reservation_id; //Update the reservation

		if (mysqli_query($link, $sql)) {
			$text = translate('toast_confirm', ["a" => $reservation_id]);
			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;

			//get devices
			$array = array();
			$sql = "SELECT device_type_name FROM `devices_of_reservations`, device_list, device_type WHERE devices_of_reservations.device_id=device_list.device_id AND device_list.device_type_id=device_type.device_type_id AND `reservation_id` = " . $reservation_id;
			if ($result = mysqli_query($link, $sql)) {
				if (mysqli_num_rows($result) > 0) {
					while ($row2 = mysqli_fetch_array($result)) {
						$array[count($array)] = $row2['device_type_name'];
					}	
					mysqli_free_result($result);
				}
			}
			else {
				throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
			}
			
			$amount = array_count_values($array);
			$devices =array();
			for ($i = 0; $i < count(array_keys($amount)); $i++) {
				if(!$devices)$devices = $amount[array_keys($amount)[$i]] . "x " . array_keys($amount)[$i];
				else $devices = ", ". $amount[array_keys($amount)[$i]] . "x " . array_keys($amount)[$i];
			}

			require_once("Controller/ICS.php");

			//create ICS file for pickup

			$ics_file_contents = createEventICS($row, $abholbar_uhrzeit, $departments, $devices);


			//send mail to user
			$messagetext = "Ihre Reservierungsanfrage #" . $reservation_id . " wurde bestätigt.<br /><br />Sie können die Reservierung am " . date_format(date_create($row['date_from']), 'd.m.Y') . " " . $abholbar_uhrzeit . " im Raum " . $row['room_from'] . " abholen.<br />Bringen Sie diese Mail als Bestätigung Ihrer Identität mit.<br /><br />Bei Fragen bezüglich Ihrer Reservierung wenden Sie sich bitte an: " . $departments[$row['department_id']]['mail'] . "<br /><br />Mit freundlichen Grüßen<br />Ihr Edurent-Team";
			sendamail($mail, $row['email'], "Reservierungsbestätigung #" . $reservation_id, $messagetext, $ics_file_contents);

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}
	
	//sqlinjection
	if(exists_and_not_empty('zu', $_GET)){ //downgrade to ...
		$sql = "UPDATE reservations SET status = 2 WHERE reservation_id=" . $_GET['zu'];
		if (mysqli_query($link, $sql)) {
			$text = "Reservierungsanfrage #" . $_GET['zu'] . " wurde zurückgestuft.";

			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if(exists_and_not_empty('abh', $_GET)){ //collected
		try {
			$SESSION->toasttext = "collected";
			/** compare old and new devices and recognize differences **/

			if(!$_GET['new']) throw new Exception("GET(new) is empty");
			if(!$_GET['new_typs']) throw new Exception("GET(new_typs) is empty");
			if(!$_GET['new_tags']) throw new Exception("GET(new_tags) is empty");

			$new_devices = explode(",", $_GET['new']);
			$old_devices = explode("|", $orders[$_GET['abh']][0]);
			$typs = explode(",", $_GET['new_typs']);
			$tags = explode(",", $_GET['new_tags']);
			$old_tags = explode("|", $orders[$_GET['abh']][2]);
			$old_typs = explode("|", $orders[$_GET['abh']][3]);
			$reservation_id = $_GET['abh'];

			$fehlt = array();
			$zuviel = array();
			$array_key_list = array();

			if(count($new_devices) != count($old_devices)) throw new Exception("count of new devices and old devices is not equal");

			for($i = 0; $i < count($new_devices); $i++) {
				if($new_devices[$i] != $old_devices[$i]) {
					array_push($fehlt, $new_devices[$i]);
					array_push($zuviel, $old_devices[$i]);
					array_push($array_key_list, $i);
				}
			}

			if(!(count($zuviel) == count($fehlt))) throw new Exception("array fehlt and array zuviel are not the same length");
			$keys = array_keys($array_key_list);
			/** device change **/
			if(count($zuviel) > 0) {
				for ($i = 0; $i < count($keys); $i++) {
					//get new device id
					$sql = "SELECT device_id FROM device_list, device_type WHERE device_list.device_tag = " . $tags[$array_key_list[$keys[$i]]] . " AND device_list.device_type_id = device_type.device_type_id AND device_type.device_type_indicator =  '" . $typs[$array_key_list[$keys[$i]]] . "'";
					$query = mysqli_query($link, $sql);
					$row = mysqli_fetch_array($query);
					if (!$row) {
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
					$new_id = $row['device_id'];

					//get old id
					$sql = "SELECT devices_of_reservations.id FROM device_list, device_type, devices_of_reservations WHERE device_list.device_tag = " . $old_tags[$array_key_list[$i]] . " AND device_list.device_type_id = device_type.device_type_id AND devices_of_reservations.device_id = device_list.device_id AND device_type.device_type_indicator =  '" . $old_typs[$array_key_list[$i]] . "' AND devices_of_reservations.reservation_id=" . $reservation_id . " LIMIT 1";
					$query = mysqli_query($link, $sql);
					$row = mysqli_fetch_array($query);
					if (!$row) {
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
					$old_id = $row['id'];

					//Update
					$sql = "UPDATE devices_of_reservations SET devices_of_reservations.device_id= '" . $new_id . "' WHERE devices_of_reservations.id='" . $old_id . "' AND devices_of_reservations.reservation_id=" . $reservation_id . " LIMIT 1";
					if (!mysqli_query($link, $sql)) {
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
				}
			}

			/** get the infos from reservation **/
			$query = mysqli_query($link, "SELECT DISTINCT email, date_to, time_to, department_id, room_to FROM user, reservations WHERE user.id=user_id AND reservation_id=" . $reservation_id);
			$row = mysqli_fetch_array($query);

			$return = date('d.m.Y', strtotime($row['date_to'])) . " " . $row['time_to'];

			/** status order **/
			//sqlinjection
			$sql = "UPDATE reservations SET status=3, date_from=now() WHERE reservation_id=" . $_GET['abh'];
			if (mysqli_query($link, $sql)) {
				$text = "Reservierungsanfrage #" . $_GET['abh'] . " wurde abgeholt.";

				save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
				$SESSION->toasttext = $text;

				//get devices
				$array = array();
				$sql = "SELECT device_type_name FROM `devices_of_reservations`, device_list, device_type WHERE devices_of_reservations.device_id=device_list.device_id AND device_list.device_type_id=device_type.device_type_id AND `reservation_id` = " . $reservation_id;
				if ($result = mysqli_query($link, $sql)) {
					if (mysqli_num_rows($result) > 0) {
						while ($row2 = mysqli_fetch_array($result)) {
							$array[count($array)] = $row2['device_type_name'];
						}	
						mysqli_free_result($result);
					}
				}
				else {
					throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
				}
				
				$amount = array_count_values($array);
				$devices = array();
				for ($i = 0; $i < count(array_keys($amount)); $i++) {
					if(!$devices)$devices = $amount[array_keys($amount)[$i]] . "x " . array_keys($amount)[$i];
					else $devices = ", ". $amount[array_keys($amount)[$i]] . "x " . array_keys($amount)[$i];
				}

				require_once("Controller/ICS.php");

				//create ics file for return

				$ics_file_contents = createEventICS($row, $return, $departments, $devices, true);

				//send mail to user
				$messagetext = "Sie haben ihre Reservierung #" . $_GET['abh'] . " abgeholt.<br /><br />Bitte bringen Sie ihre Reservierung am " . $return . " im Raum " . $row['room_to'] . " zurück.<br /><br />Bei Fragen bezüglich Ihrer Reservierung wenden Sie sich bitte an: " . $departments[$row['department_id']]['mail'] . "<br /><br />Mit freundlichen Grüßen<br />Ihr Edurent-Team";
				sendamail($mail, $row['email'], "Reservierung #" . $_GET['abh'] . " wurde abgeholt", $messagetext, $ics_file_contents);
			} else {
				throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
			}
			echo "<script>window.location.href = 'admini';</script>";
		}
		catch (exception $e) {
			error_to_superadmin(get_superadmins(), $mail, "ERROR: in 409 admini: " . $e->getMessage());			
		}
	}

	if(exists_and_not_empty('cancel', $_GET)){ //deleted
		//sqlinjection
		$sql = "UPDATE reservations SET status ='6' WHERE reservation_id=" . $_GET['cancel'];
		if (mysqli_query($link, $sql)) {
			$text = "Reservierungsanfrage #" . $_GET['cancel'] . " wurde abgebrochen.";

			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if(exists_and_not_empty('extend', $_GET)){ //edited
		try {
			$mail;
			$sql = "SELECT email, mail, departments.department_id FROM user, reservations, departments WHERE id=user_id AND reservations.department_id = departments.department_id AND reservation_id=" . $_GET['extend'];
			if ($result = mysqli_query($link, $sql)) {
				$row = mysqli_fetch_array($result);
				$email_user = $row['email'];
				$department_mail = $row['mail'];
				$department_id = $row['department_id'];
			}
			else {
				throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
			}

			//sqlinjection
			$sql = "UPDATE reservations SET date_from ='" . date($_GET['date_from']) . "', date_to ='" . date($_GET['date_to']) . "', room_to='" . $_GET['room_to'] . "', room_from='" . $_GET['room_from'] . "', time_from='" . $_GET['time_from'] . "', time_to='" . $_GET['time_to'] . "' WHERE reservation_id=" . $_GET['extend'];
			if (!mysqli_query($link, $sql)) {
				throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
			}

			$text = "Reservierungsanfrage #" . $_GET['extend'] . " wurde bearbeitet.";
			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;

			$date_from = date('d.m.Y', strtotime($_GET['date_from']));
			$date_to = date('d.m.Y', strtotime($_GET['date_to']));

			$new_values = array_filter(explode("|", $_GET['values']));
			$new_indicators = array_filter(explode("|", $_GET['indicators']));

			$types = explode("|", $_GET['types']);
			$amounts = explode("|", $_GET['amounts']);
			//alle geräte löschen
			$sql = "DELETE FROM `devices_of_reservations` WHERE devices_of_reservations.reservation_id=" . $_GET['extend'];
			if (!mysqli_query($link, $sql)) {
				throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
			}

			/** add devices **/		
			if(count($new_indicators) > 0) {
				for ($i = 0; $i < count($new_indicators); $i++) {
					//get id
					$sql = "SELECT device_id FROM device_list, device_type WHERE device_list.device_tag = " . $new_values[$i] . " AND device_list.device_type_id = device_type.device_type_id AND device_type.device_type_indicator =  '" . $new_indicators[$i] . "'";
					$query = mysqli_query($link, $sql);
					$row = mysqli_fetch_array($query);
					if (!$row) {
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
					$new_id = $row['device_id'];

					//Add
					$sql = "INSERT INTO `devices_of_reservations`(`reservation_id`, `device_id`) VALUES ('" . $_GET['extend'] . "','" . $new_id . "')";
					if (!mysqli_query($link, $sql)) {
						throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
				}
			}

			require_once("Controller/basic.php");
			$reservated = get_active_reservations();
			$not_blocked_devices = get_notblockeddevices();
			$id_by_name = get_idbyname();

			//anzahl hinzufügen
			$not_blocked_devices = available_devices($reservated, $department_id, $not_blocked_devices, $_GET['date_from'], $_GET['date_to']);

			for($i = 0; $i < count($types); $i++){
				if($amounts[$i] < 1 || $amounts[$i] == "") continue;
				for($u = 0; $u < $amounts[$i]; $u++){
					if(!isset($not_blocked_devices[$id_by_name[$types[$i]]])) break;
					$device_type_id = $id_by_name[$types[$i]];
					$device_keys = array_keys($not_blocked_devices[$device_type_id]);
					$d_id = $not_blocked_devices[$device_type_id][$device_keys[$u]];
					$query = "INSERT INTO devices_of_reservations (reservation_id,device_id) VALUES (?,?)";
					if ($stmt = mysqli_prepare($link, $query)) {
						mysqli_stmt_bind_param($stmt, "ii", $_GET['extend'], $d_id);
						if (!mysqli_stmt_execute($stmt)) throw new Exception("ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					} else throw new Exception("ERROR: Could not prepare statement. " . mysqli_error($link));
				}
			}

			$messagetext = "Ihre Reservierung mit der ID #" . $_GET['extend'] . " wurde von einem Admin bearbeitet.<br /><br />Folgende Informationen sind Ihrer Reservierung zugehörig:<br /><br />Bei Fragen bezüglich Ihrer Reservierung wenden Sie sich bitte an: " . $department_mail . "<br /><br />Mit freundlichen Grüßen<br />Ihr Edurent-Team";
			
			sendamail($mail, $email_user, "Edurent - #" . $_GET['extend'] . " wurde bearbeitet", $messagetext);
			echo "<script>window.location.href = 'admini';</script>";
		}
		catch (exception $e) {
			error_to_superadmin(get_superadmins(), $mail, "ERROR: in 517 admini: " . $e->getMessage());			
		}
	}

	if(exists_and_not_empty('ret', $_GET)){ //retour
		//sqlinjection
		$sql = "UPDATE reservations SET status ='4', date_to=now() WHERE reservation_id=" . $_GET['ret'];
		if (mysqli_query($link, $sql)) {
			$text = "Reservierungsanfrage #" . $_GET['ret'] . " wurde zurückgegeben.";

			save_in_logs("INFO: " . $text, $user_firstname, $user_lastname, false);
			$SESSION->toasttext = $text;

			echo "<script>window.location.href = 'admini';</script>";
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if(exists_and_not_empty('rem', $_GET)){ //Delete from reservations & devices_of_reservations where reservation_id
		//sqlinjection
		$sql = "DELETE reservations FROM reservations WHERE reservations.reservation_id = " . $_GET['rem'];
		if (mysqli_query($link, $sql)) {
			if(mysqli_affected_rows($link) > 0){
				$text = "INFO: Reservierungshistorie #" . $_GET['rem'] . " wurde gelöscht.";

				save_in_logs($text, $user_firstname, $user_lastname, false);
				$SESSION->toasttext = $text;

				echo "<script>window.location.href = 'admini';</script>";
			}
			else error_to_superadmin(get_superadmins(), $mail, "Reservierungshistorie #" . $_GET['rem'] . " konnte nicht gelöscht werden.");
		} else {
			$error = "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link);
			error_to_superadmin(get_superadmins(), $mail, $error);
		}
	}

	if (is_superadmin($user_username)) { //Show the active reservations
		$sql = "SELECT DISTINCT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to, time_from, time_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND (status<4 OR status >6) AND user_id=user.id ORDER BY reservation_id";
	} else {
		$ids;
		for ($i=0; $i < count($department_ids); $i++) {
			if($i == 0) $ids = "(reservations.department_id=" . $department_ids[$i];
			else $ids .= " OR reservations.department_id=" . $department_ids[$i];
		}
		$ids .= ")"; 
		$sql = "SELECT DISTINCT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to, time_from, time_to  FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND (status<4 OR status >6) AND user_id=user.id AND " . $ids . " ORDER BY reservation_id";
	}

	?>
		<div class="main">
	<?php

	if ($result = mysqli_query($link, $sql)) {
		if (mysqli_num_rows($result) > 0) {
	?>
		
			<h3 style='text-align:center;' class="select"> <?php echo translate('word_reservations'); ?> </h3>
			<div class='table-responsive'>
				<table class='table table-sortable'>
					<thead>
						<tr>
							<th class='band'>
								<?php echo translate('word_number'); ?>
							</th>
							<th class='band'>
								<?php echo translate('word_status'); ?>
							</th>
							<th class='band'>
								<?php echo translate('word_deadline'); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php /** Get the reservations **/
						while ($row = mysqli_fetch_array($result)) {
						?>
							<tr>
								<?php
								$abholbar_timestamp = strtotime($row['date_from']);
								$abholbar_wochentag = date("N", $abholbar_timestamp) % 7; //7 = 0
								$abholbar_uhrzeit;

								$days = array_keys($pickupdays[$row['department_id']]);
								for ($i = 0; $i < count($days); $i++) {
									if ($abholbar_wochentag == $pickupdays[$row['department_id']][$days[$i]]['dayofweek']) {
										$abholbar_uhrzeit = $pickupdays[$row['department_id']][$days[$i]]['time'];
										break;
									}
								}

								$rueckgabe_timestamp = strtotime($row['date_to']);
								$rueckgabe_wochentag = date("N", $rueckgabe_timestamp) % 7; //7 = 0
								$rueckgabe_uhrzeit;

								$days = array_keys($pickupdays[$row['department_id']]);
								for ($i = 0; $i < count($days); $i++) {
									if ($rueckgabe_wochentag == $pickupdays[$row['department_id']][$days[$i]]['dayofweek']) {
										$rueckgabe_uhrzeit = $pickupdays[$row['department_id']][$days[$i]]['time'];
										break;
									}
								}

								switch ($row['status']) {
									case 1:
										$status = translate('status_1');
										$button = "btn-warning";
										break;
									case 2:
										$status = translate('status_2');
										$button = "btn-info";
										break;
									case 3:
										$status = translate('status_3');
										$button = "btn-success";
										break;
									case 7:
										$status = translate('status_7');
										$button = "btn-danger";
										break;
									default:
										$status = translate('status_4');
										$button = "btn-danger";
										break;
								}

								//if anfrage überfällig
								$current = date("Y-m-d");
								$frist = $row['date_from'];
								$date1 = date_create($current);
								$date2 = date_create($frist);
								$diff = date_diff($date1, $date2);
								$diff = $diff->format("%R%a");
								if ($row['status'] == 1 && $diff < 0) {
									$status = "Anfrage Überfällig";
									$button = "btn-danger";
								}

								//if abholbar überfällig
								if ($row['status'] == 2 && $diff < 0) {
									$status = "Abholung Überfällig";
									$button = "btn-danger";
								}

								//if rückgabe überfällig
								$frist = $row['date_to'];
								$date2 = date_create($frist);
								$diff = date_diff($date1, $date2);
								$diff = $diff->format("%R%a");
								if ($row['status'] == 3 && $diff < 0) {
									$status = "Rückgabe Überfällig";
									$button = "btn-danger";
								}
								?>
								<td> <button type="button" class="btn rounded <?php echo $button; ?> mr-1 mb-1" onclick="modal_load(<?php echo $row['reservation_id']; ?>,'<?php echo date_format(date_create($row['date_from']), 'd.m.Y'); ?>','<?php echo date_format(date_create($row['date_to']), 'd.m.Y'); ?>','<?php echo $row['fn']; ?>','<?php echo $row['ln']; ?>','<?php echo $row['status']; ?>','<?php echo $row['room_from']; ?>','<?php echo $row['room_to']; ?>','<?php echo $departments[$row['department_id']][get_language()]; ?>','<?php echo $row['time_from']; ?>','<?php echo $row['time_to']; ?>')">#<?php echo $row['reservation_id']; ?></button></td>
								<td style="vertical-align: middle"><?php echo $status; ?></td>

								<?php
								switch ($row['status']) {
									case 1: //pickup 
										echo "<td></td>";
										break;
									case 2: //pickup 
										echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_from']), 'd.m.Y') . "</td>";
										break;
									case 3: //request 
										echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_to']), 'd.m.Y') . "</td>";
										break;
									case 7: //error 
										echo "<td style='vertical-align: middle'></td>";
										break;
								}
								?>
							</tr>
						<?php
						}
						mysqli_free_result($result);
						?>
					</tbody>
				</table>
			</div>

			<?php } else { ?>

				<div style="text-align:center; width:100%; max-width: 80ch; margin: 0 auto;">
					<h3 style='text-align:center;'>
						Es gibt keine offenen Reservierungen
					</h3>
				</div>

			<?php
		}
	} else {
		error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
	}
	/** END - Show the active reservations **/

	/** START - orderhistory **/
	$sql;


	if ($department_ids[0] == 0) { //is superadmin
		$sql = "SELECT DISTINCT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND status>3 AND status <7 AND user_id=user.id ORDER BY reservation_id DESC";
	} else {
		$departments_sql = "(reservations.department_id='" . $department_ids[0] . "'";
		for($i = 1; $i < count($department_ids); $i++) {
			$departments_sql .= " OR reservations.department_id='" . $department_ids[$i] . "'";
		}
		$departments_sql .= ")";
		$sql = "SELECT DISTINCT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND status>3 AND status <7 AND user_id=user.id AND " . $departments_sql . " ORDER BY reservation_id DESC";
	}

	if ($result = mysqli_query($link, $sql)) {
		if (mysqli_num_rows($result) > 0) {
			?>
				<br>
				<div class='row no-gutters text-center'>
					<div class='col'></div>
					<h3 class='m-b-0 col select'><?php echo translate('word_orderHistory'); ?></h3>
					<div class='col'>
						<input type='text' class='w-100 search' title='Bestellungen = #2<br>Geräte\nPersonen\n<?php echo translate('word_date'); ?> = 05.08.2023' placeholder='<?php echo translate('word_search'); ?>' />
					</div>
				</div>

				<div class="table-responsive">
					<table class='table results collapse_me'>
						<thead>
							<tr>
								<th class='band' scope='col'><?php echo translate('word_number'); ?></th>
								<th class='band' scope='col'><?php echo translate('word_dateFrom'); ?></th>
								<th class='band' scope='col'><?php echo translate('word_dateTo'); ?></th>
								<th class='band' scope='col'><?php echo translate('word_status'); ?></th>
							</tr>
							<tr class='warning no-result' style="display:none;">
								<td colspan='4'><i class='fa fa-warning'></i><?php echo translate('text_noResult'); ?></td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan='4' class='clickable'><?php echo translate('text_tablehead'); ?></td>
							</tr>
						</tbody>
						<tbody class="collapsed">
							<?php while ($row = mysqli_fetch_array($result)) { ?>
								<tr>
									<td> <button type="button" class="btn rounded btn-outline-dark mr-1 mb-1" onclick="modal_load(<?php echo $row['reservation_id']; ?>,'<?php echo date_format(date_create($row['date_from']), 'd.m.Y'); ?>','<?php echo date_format(date_create($row['date_to']), 'd.m.Y'); ?>','<?php echo $row['fn']; ?>','<?php echo $row['ln']; ?>','<?php echo $row['status']; ?>','<?php echo $row['room_from']; ?>','<?php echo $row['room_to']; ?>','<?php echo $departments[$row['department_id']][get_language()]; ?>')">#<?php echo $row['reservation_id']; ?></button></td>
								<?php
								switch (!is_null($row['date_from'])) { //column from
									case true:
										echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_from']), 'd.m.Y') . "</td>";
										break;
									case false:
										echo "<td></td>";
										break;
								}

								switch (!is_null($row['date_to'])) { //column to
									case true:
										echo "<td style='vertical-align: middle'>" . date_format(date_create($row['date_to']), 'd.m.Y') . "</td>";
										break;
									case false:
										echo "<td></td>";
										break;
								}
								$status = get_history_status($row['status']);
								echo "<td style='vertical-align: middle'>" . $status . "</td>";

								/** hidden infos for searchbar **/
								echo "<td style='display:none;'>#" . $row['reservation_id'] . "</td>"; //reservation id
								echo "<td style='display:none;'>" . $row['fn'] . " " . $row['ln'] . "</td>"; //full name
								if(exists_and_not_empty($row['reservation_id'],$orders)){
									if(exists_and_not_empty(0,$orders[$row['reservation_id']])){ //devices
										echo "<td style='display:none;'>" . $orders[$row['reservation_id']][0] . "</td>"; //device ids
									}
								}
								echo "</tr>";
							}
							echo "</tbody>";
							echo "</table>";
							echo "</div>";
							mysqli_free_result($result);
						} else { ?>
								<br>
								<br>
								<h5 style='text-align:center;'>Es gibt keine vergangenen Reservierungen</h5>
						<?php }
					} else {
						error_to_superadmin(get_superadmins(), $mail, "ERROR: Could not able to execute: " . $sql . ": " . mysqli_error($link));
					}
					// END - orderhistory

					//START - Links
						?>
						<br>
						<h3 style='text-align:center;' class="select"><?php echo translate('word_systemmanagement'); ?></h3>
						<div class='row justify-content-center'>
						<?php if (is_admin($user_username)) { ?>
							<div class='col-12 col-md-4 mb-3'>
								<a class='btn btn-outline-dark btn-block' href='departments'>
									<i class="fa-solid fa-building"></i> <?php echo translate('word_departments'); ?>
								</a>
							</div>
						<?php } ?>
						<div class='col-12 col-md-4 mb-3'>
							<a class='btn btn-outline-dark btn-block' href='faq'>
								<i class="fas fa-question-circle mr-2"></i> FAQ
							</a>
						</div>
						<?php if (is_superadmin($user_username)) { ?>
							<div class='col-12 col-md-4 mb-3'>
								<a class='btn btn-outline-dark btn-block' href='admins'>
									<i class="fas fa-user-cog mr-2"></i> <?php echo translate('word_admins'); ?>
								</a>
							</div>
							<div class='col-12 col-md-4 mb-3'>
								<a class='btn btn-outline-dark btn-block' href='logs'>
									<i class="fas fa-file-alt mr-2"></i> Logs
								</a>
							</div>
							<div class='col-12 col-md-4 mb-3'>
								<a class='btn btn-outline-dark btn-block' href='update_settings'>
									<i class="fas fa-cog mr-2"></i> <?php echo translate('word_settings'); ?>
								</a>
							</div>
						<?php } ?>
					</div>
				</div>
	<!-- tablesort -->
	<script type="text/javascript" src="js/tablesort.js"></script>
</body>
<?php
//END - Links

echo $OUTPUT->footer();
mysqli_close($link);

//Functions PHP

function get_history_status($status_id)
{
	switch ($status_id) {
		case 4:
			$status = translate('status_4');
			break;
		case 5:
			$status = translate('status_5');
			break;
		case 6:
			$status = translate('status_6');
			break;
		default:
			$status = "Fehler";
			break;
	}
	return $status;
}
?>
<script>
	document.querySelectorAll(".collapse_me th").forEach(headerCell => {
		headerCell.addEventListener("click", () => {
			var tbodyCollapsed = document.querySelector(".collapse_me tbody.collapsed");
			var tbodyVisible = document.querySelector(".collapse_me tbody:not(.collapsed)");

			if (tbodyCollapsed && tbodyVisible) {
				tbodyCollapsed.classList.remove("collapsed");
				tbodyVisible.classList.add("collapsed");
			}
		});
	});

	var added = 0;
	function order_cancel(reservation_id) {
		location.href = "?cancel=" + reservation_id;
	}

	function add_function(){
		var devices_of_deparment = <?php echo is_null($devices_of_deparment) ? "2" : json_encode($devices_of_deparment); ?>;

		if(devices_of_deparment == "2") return;
		//var string = document.getElementById('device_list').innerHTML;
		added++;

		var string = "<div class='row no-gutters' style='text-align:center;' name='device_list_" + added + "' id='device_list_" + added + "'>";
			string += "<div class='col'>";
				string += "<select class='form-select' name='type_" + added + "' id='type_" + added + "'>";
					for (var i = 0; i < devices_of_deparment.length; i++) {
						string += "<option id='device_" + devices_of_deparment[i]['device_type_indicator'] + "' name='device_" + devices_of_deparment[i]['device_type_indicator'] + "'>" + devices_of_deparment[i]['device_type_name'] + "</option>";
					}
				string += "</select>";
			string += "</div>";
			string += "<div class='col col-lg-2'>";
				string += "<input type='number' name='amount_" + added + "' id='amount_" + added + "' class='form-control' value='1'>"; 
			string += "</div>";
			string += "<div class='col col-lg-2'>";
				string += "<button onclick='remove_function(" + added + ")'>-</button>";
			string += "</div>";
		string += "</div>";

		var str = '<p>Some more <span>text</span> here</p>',
		div = document.getElementById( 'device_list' );
		div.insertAdjacentHTML( 'beforeend', string );
	}

	function remove_function(id){
		document.getElementById('device_list_' + id).innerHTML = "";
	}

	function order_extend(reservation_id, from, to, time_from, time_to, room_from, room_to) { //extend the timespan of a reservation
		var mymodal = $('#my_modal');
		titel = translate("<?php echo translate('text_editTitle'); ?>", [reservation_id]);
		mymodal.find('.modal-title').text(titel);

		var orders = <?php echo is_null($orders) ? "2" : json_encode($orders); ?>;

		var string = "";

		$("#button_yellow").hide();
		$("#button_green").hide();
		$("#button_grey").show();
		$("#button_red").show();

		if (orders == "2") {
			string = '<center>Es konnten keine zugeordneten Geräte gefunden werden. Bitte untersuchen die die Reservierung in der MySQL Datenbank.</center>';
			$("#button_red").hide();
		} else {
			var pattern = /(\d{2})\.(\d{2})\.(\d{4})/;
			var date_from = from.replace(pattern, '$3-$2-$1');
			var date_to = to.replace(pattern, '$3-$2-$1');

			var string_translate = translate("<?php echo translate('text_editReservation1'); ?>", [from, time_from, room_from]);
			string += "<center>" + string_translate + "<br>";

			string += "<div class='row no-gutters' style='text-align:center;'>";
			string += "<div class='col'>";
			string += "<?php echo translate('word_date'); ?>: ";
			string += "</div>";
			string += "<div class='col input-group'>";
			string += "<input type='date' class='form-control rounded' id='date_from' name = 'date_from' value='" + date_from + "'>";
			string += "</div>";
			string += "</div>";

			string += "<div class='row no-gutters' style='text-align:center;'>";
			string += "<div class='col'>";
			string += "<?php echo translate('word_time'); ?>: ";
			string += "</div>";
			string += "<div class='col input-group'>";
			string += "<input type='text' class='form-control rounded' id='time_from' name = 'time_from' value='" + time_from + "'>";
			string += "</div>";
			string += "</div>";

			string += "<div class='row no-gutters' style='text-align:center;'>";
			string += "<div class='col'>";
			string += "<?php echo translate('word_room'); ?>: ";
			string += "</div>";
			string += "<div class='col input-group'>";
			string += "<input type='text' class='form-control rounded' id='room_from' name = 'room_from' value='" + room_from + "'>";
			string += "</div>";
			string += "</div>";
			string += "<br>";

			var string_translate = translate("<?php echo translate('text_editReservation2'); ?>", [to, time_to, room_to]);
			string += string_translate + "<br>";

			string += "<div class='row no-gutters' style='text-align:center;'>";
			string += "<div class='col'>";
			string += "<?php echo translate('word_date'); ?>: ";
			string += "</div>";
			string += "<div class='col input-group'>";
			string += "<input type='date' class='form-control rounded' id='date_to' name = 'date_to' value='" + date_to + "'>";
			string += "</div>";
			string += "</div>";

			string += "<div class='row no-gutters' style='text-align:center;'>";
			string += "<div class='col'>";
			string += "<?php echo translate('word_time'); ?>: ";
			string += "</div>";
			string += "<div class='col input-group'>";
			string += "<input type='text' class='form-control rounded' id='time_to' name = 'time_to' value='" + time_to + "'>";
			string += "</div>";
			string += "</div>";

			string += "<div class='row no-gutters' style='text-align:center;'>";
			string += "<div class='col'>";
			string += "<?php echo translate('word_room'); ?>: ";
			string += "</div>";
			string += "<div class='col input-group'>";
			string += "<input type='text' class='form-control rounded' id='room_to' name = 'room_to' value='" + room_to + "'>";
			string += "</div>";
			string += "</div>";
			string += "<br>";

			string += "<?php echo translate('word_deviceList'); ?>:<br>";
			string += "<div id='device_list' name='device_list'>";

			if(orders[reservation_id] != undefined){
				var names = orders[reservation_id][1].split('|');
				var tag = orders[reservation_id][2].split('|');
				var type_indicator = orders[reservation_id][3].split('|');

				for (var i = 0; i < names.length; i++) {
					string += "<div class='row no-gutters' style='text-align:center;' name='device_list_" + i + "' id='device_list_" + i + "'>";
						string += "<div class='col col-lg-2' name='type_" + i + "' id='type_" + i + "'>";
							string += names[i];
						string += "</div>";
						string += "<div class='col input-group'>";
							string += "<span style='width:7ch; justify-content: right; display: flex;' class='input-group-text' id='indicator_" + i + "' name = 'indicator_" + i + "'>" + type_indicator[i] + "</span>";
							string += "<input type='text' style='height:100%' maxlength='<?php echo $limits['device_tag']; ?>' class='form-control rounded' id='device_" + i + "' name = 'device_" + i + "' value='" + tag[i] + "'>";
						string += "</div>";
						string += "<div class='col col-lg-2' style='align-items: center; justify-content: center;display: flex;'>";
							string += "<button class='btn btn-outline-secondary' style='height: 90%; display: flex; align-items: center;' onclick='remove_function(" + i + ")'>-</button>";
						string += "</div>";
					string += "</div>";
				}
				added = names.length-1;
			}
			string += "</div>";
			string += "<br>";
			string += "<button onclick='add_function()'>+</button>"

			$("#button_red").html('<?php echo translate('word_editBig'); ?>');
			$("#button_red").attr("onclick", "order_extend2(" + reservation_id + ")");
			string += '</center>';
		}
		mymodal.find('.modal-body').html(string);
		mymodal.modal('show');
	}

	function order_extend2(reservation_id) {
		from = document.getElementById("date_from").value;
		time_from = document.getElementById("time_from").value;
		room_from = document.getElementById("room_from").value;

		to = document.getElementById("date_to").value;
		time_to = document.getElementById("time_to").value;
		room_to = document.getElementById("room_to").value;

		//get device infos
		var types = "";
		var amounts = "";
		var values = "";
		var indicators = "";
		for (var i = 0; i <= added; i++) {
			if(!document.getElementById("type_" + i)) continue;
			var value = document.getElementById("type_" + i).value;
			if(value != ""){
				if(value == undefined){
					values += document.getElementById("device_" + i).value + "|";
					indicators += document.getElementById("indicator_" + i).innerHTML + "|";
				}
				else{
					types += document.getElementById("type_" + i).value + "|";
					amounts += document.getElementById("amount_" + i).value + "|";
				}
			}
		}

		location.href = "?extend=" + reservation_id + "&date_from=" + from + "&date_to=" + to + "&room_from=" + room_from + "&room_to=" + room_to + "&time_to=" + time_to + "&time_from=" + time_from + "&types=" + types + "&amounts=" + amounts + "&values=" + values + "&indicators=" + indicators;
	}

	function order_remove(reservation_id) {
		location.href = "?rem=" + reservation_id;
	}

	function order_accept(reservation_id) {
		location.href = "?org=" + reservation_id;
	}

	function order_back(reservation_id) {
		location.href = "?zu=" + reservation_id;
	}

	function order_retour(reservation_id) {
		location.href = "?ret=" + reservation_id;
	}

	function order_pickup2(reservation_id, amount) {
		var devices = "";
		var typs = "";
		var bouth = "";
		for (var i = 0; i < amount; i++) {
			if (devices != "") {
				devices += ",";
				typs += ",";
				bouth += ",";
			}
			devices += $("#device" + i).val();
			typs += document.getElementById("type_indicator" + i).innerText;
			bouth += document.getElementById("type_indicator" + i).innerText + $("#device" + i).val();
		}
		location.href = "?abh=" + reservation_id + "&new_tags=" + devices + "&new_typs=" + typs + "&new=" + bouth;
	}

	function order_pickup(reservation_id) { //confirm the issued devices
		console.log("2");
		var mymodal = $('#my_modal');
		var titel = "<?php echo translate('word_order'); ?> #" + reservation_id;
		mymodal.find('.modal-title').text(titel);

		var orders = <?php echo is_null($orders) ? "2" : json_encode($orders); ?>;

		var string = "";

		$("#button_grey").show();
		$("#button_green").show();
		$("#button_red").show();
		$("#button_yellow").hide();

		if (orders == "2") {
			string = '<center>Es konnten keine zugeordneten Geräte gefunden werden. Bitte untersuchen die die Reservierung in der MySQL Datenbank.</center>';
			$("#button_red").hide();
			$("#button_green").hide();
		} else {
			var names = orders[reservation_id][1].split('|');
			var tag = orders[reservation_id][2].split('|');
			var type_indicator = orders[reservation_id][3].split('|');
			var geraete = "";
			var amount = 0;

			string += "<center>Welche Geräte-IDs wurden ausgegeben?<br>";

			for (var i = 0; i < names.length; i++) {
				string += "<div class='row no-gutters' style='text-align:center;'>";
				string += "<div class='col'>";
				string += names[i];
				string += "</div>";
				string += "<div class='col input-group'>";
				string += "<span class='input-group-text' id='type_indicator" + i + "' name = 'type_indicator" + i + "'>" + type_indicator[i] + "</span>";
				string += "<input type='text' maxlength='<?php echo $limits['device_tag']; ?>' class='form-control rounded' id='device" + i + "' name = 'device" + i + "' value='" + tag[i] + "'>";
				string += "</div>";
				string += "</div>";
				amount++;
			}
			$("#button_red").attr("onclick", "order_pickup2(" + reservation_id + "," + amount + ")");
			string += '</center>';
		}
		mymodal.find('.modal-body').html(string);
		mymodal.modal('show');
	}
</script>