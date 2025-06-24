<!DOCTYPE HTML>
<?php
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

check_is_admin($user_username);

$is_superadmin = is_superadmin($user_username);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $_SESSION['reservation_id'] = $reservation_id;

    echo "<script>window.location.href = 'view_reservation';</script>";
    exit;
} 

$reservation_id = $_SESSION['reservation_id'] ?? null;

//get order
$sql = "SELECT reservations.reservation_id, date_from, date_to, status, fn, user.id, ln, departments.department_id, room_from, room_to FROM reservations, user, departments WHERE departments.department_id=reservations.department_id AND reservations.user_id=user.id AND user_id=user.id AND reservations.reservation_id=" . $reservation_id ;
if ($result = mysqli_query($link, $sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $department_id = $row['department_id'];
            $reservation_id = $row['reservation_id'];
            $date_from = $row['date_from'];
            $date_to = $row['date_to'];
            $status = $row['status'];
            $fn = $row['fn'];
            $ln = $row['ln'];
            $room_from = $row['room_from'];
            $room_to = $row['room_to'];
        }
    } else {
        echo "No results found.";
    }
} else {
    echo "Error: " . mysqli_error($link);
}

//get orders you are allowed to see
$orders = array();
if ($is_superadmin) {
    $orders = get_all_orders();
} else {
	foreach ($department_ids as $department_id) {
        $department_orders = get_all_orders_from_department($department_id);
        if ($department_orders) {
            $orders = $orders + $department_orders;
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
    <link rel="stylesheet" href="style-css/page_colors.scss">
    <link rel="stylesheet" href="style-css/accessability.css">
    <link rel="stylesheet" href="style-css/navbar.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    
    <!-- Toast -->
    <?php require_once("Controller/toast.php"); ?>
</head>
<body>
    <div class="main">
        <?php require_once("app/navbar.php"); ?>
        <br>
    
        <?php
        if (isset($reservation_id )) {
            $formatted_from = date_create($date_from)->format('d.m.Y');
            $formatted_to = date_create($date_to)->format('d.m.Y');
            echo "<h1>" . translate('word_reservation') . " " . htmlspecialchars($reservation_id) . "</h1>";
            echo "Von: " . htmlspecialchars($fn) . " " . htmlspecialchars($ln);
            echo "<br>";
            echo "Institut: " . htmlspecialchars($department_id);
            echo "<br>";
            echo "Zeitraum: " . htmlspecialchars($formatted_from) . " bis " . htmlspecialchars($formatted_to);
            echo "<br>";
            $d_ids = explode('|', $orders[$reservation_id][0]);
            $names = explode('|', $orders[$reservation_id][1]);
            $geraete = "Geräte:<br>";
            for ($i = 0; $i < count($d_ids); $i++) {
                $geraete .= $d_ids[$i] . ", " . $names[$i] . "<br>";
            }
            echo $geraete;
            
        } else {
            echo "<script>window.location.href = 'admini';</script>";
        }
        ?>
        <br>
        <!-- Buttons -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php if($status == 1) { //request?>
                <button type='button' class='btn btn-danger rounded' onclick='order_cancel("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('word_cancel'); ?>
                </button>
                <button type='button' class='btn btn-success rounded' onclick='order_accept("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('word_confirm'); ?>
                </button>
            <?php } elseif($status == 2) { //Collectable ?>
                <button type='button' class='btn btn-danger rounded' onclick='order_cancel("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('word_cancel'); ?>
                </button>
                <button type='button' class='btn btn-success rounded' onclick='order_pickup("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('status_3'); ?>
                </button>
            <?php } else if($status == 3) { //Retrieved ?>
                <button type='button' class='btn btn-danger rounded' onclick='order_back("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('word_downgrade'); ?>
                </button>
                <button type='button' class='btn btn-success rounded' onclick='order_retour("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('word_return'); ?>
                </button>
            <?php } else if($status == 4 || $status == 6) { //Completed or Cancelled ?>
                <button type='button' class='btn btn-danger rounded' onclick='order_remove("<?php echo $reservation_id; ?>")'>
                    <?php echo translate('word_delete'); ?>
                </button>
            <?php }?>

            <form method="POST" action="edit_reservation" style="display:inline;">
                <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                <button type='submit' class='btn btn-warning rounded'>
                    <?php echo translate('word_editBig'); ?>
                </button>
            </form>
        </div>
    </div>
</body>

<?php
    echo $OUTPUT->footer();
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

    function order_cancel(reservation_id) {
		location.href = "admini?cancel=" + reservation_id;
	}

    function order_remove(reservation_id) {
		location.href = "admini?rem=" + reservation_id;
	}

	function order_accept(reservation_id) {
		location.href = "admini?org=" + reservation_id;
	}

	function order_back(reservation_id) {
		location.href = "admini?zu=" + reservation_id;
	}

	function order_retour(reservation_id) {
		location.href = "admini?ret=" + reservation_id;
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
		location.href = "admini?abh=" + reservation_id + "&new_tags=" + devices + "&new_typs=" + typs + "&new=" + bouth;
	}

	function order_pickup(reservation_id) { //confirm the issued devices
		var mymodal = $('#my_modal');
		var titel = "<?php echo translate('word_order'); ?> #" + reservation_id;
		mymodal.find('.modal-title').text(titel);

		var orders = <?php echo is_null($orders) ? "2" : json_encode($orders); ?>;

		var string = "";

		$("#button_grey").show();
		$("#button_green").show();
		$("#button_red").show();
		$("#button_yellow").hide();

        $("#button_red").html('<?php echo translate('word_confirm'); ?>');

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