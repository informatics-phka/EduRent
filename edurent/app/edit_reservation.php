<!DOCTYPE HTML>
<?php
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

check_is_admin($user_username);

session_start();

$is_superadmin = is_superadmin($user_username);
$all_user = get_all_user();

// define navbar
$menuItems = [
    ['label' => translate('word_reservations'), 'href' => 'admini', 'visible' => true],
    ['label' => translate('word_orderHistory'), 'href' => 'orderhistory', 'visible' => true],
    ['label' => translate('word_departments'), 'href' => 'departments', 'visible' => true],
    ['label' => translate('word_faq'), 'href' => 'faq', 'visible' => true],
    ['label' => translate('word_admins'), 'href' => 'admins', 'visible' => $is_superadmin],
    ['label' => translate('word_logs'), 'href' => 'logs', 'visible' => $is_superadmin],
    ['label' => translate('word_settings'), 'href' => 'update_settings', 'visible' => $is_superadmin],
];

$menuItemsHtml = '';
foreach ($menuItems as $item) {
    if ($item['visible']) {
        $menuItemsHtml .= '<li class="nav-item">';
        $menuItemsHtml .= '<a class="nav-link" href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['label']) . '</a>';
        $menuItemsHtml .= '</li>';
    }
}

$reservation_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $_SESSION['reservation_id'] = $reservation_id;
} elseif (isset($_SESSION['reservation_id'])) {
    $reservation_id = $_SESSION['reservation_id'];
}

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

uasort($all_user, function($a, $b) {
    $ln_cmp = strcmp($a['ln'], $b['ln']);
    if ($ln_cmp === 0) {
        return strcmp($a['fn'], $b['fn']);
    }
    return $ln_cmp;
});

//daterangepicker
$lead_time_days = 0;
$days_bookable_in_advance = 365;
$max_loan_duration = 365;
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
		<link rel="stylesheet" href="style-css/navbar.css">

        <!-- Select2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
		
		<!-- Font Awesome -->
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
		
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>

        <!-- daterangepicker -->
        <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
	</head>
    <div class="main">
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav ms-auto" id="navbarMenu">
                        <?= $menuItemsHtml ?>
                    </ul>
                </div>
            </div>
        </nav>
        <br>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($reservation_id )) {
                $reservation_id = $reservation_id ;
                $formatted_from = date_create($date_from)->format('d.m.Y');
                $formatted_to = date_create($date_to)->format('d.m.Y');
                echo "<h1>" . translate('word_reservation') . " " . htmlspecialchars($reservation_id) . "</h1>";
                echo '<label for="user_select">Ausleihende Person wählen:</label>';
                echo '<select id="user_select" class="form-control" name="selected_user">';
                foreach ($all_user as $id => $user) {
                    if (empty($user['fn']) || empty($user['ln'])) {
                        continue;
                    }
                
                    $value = htmlspecialchars($id);
                    $label = htmlspecialchars($user['fn'] . " " . $user['ln']);
                    echo "<option value='$value'>$label</option>";
                }
                echo '</select>';
                echo "<br>";
                echo "Institut: " . htmlspecialchars($department_id);
                echo "<br>";
                echo "Zeitraum: ";
                echo "<input type='text' name='daterange' id='daterange' style='text-align:center; width:100%; max-width: 27ch;' placeholder='Keine Reservierung möglich' />";
                echo "<br>";
                echo "<input type='hidden' id='date_from' name='date_from' value=''>";
                echo "<input type='hidden' id='date_to' name='date_to' value=''>";

                echo "<br>";
                $d_ids = explode('|', $orders[$reservation_id][0]);
				$names = explode('|', $orders[$reservation_id][1]);
				$geraete = "Geräte:<br>";
				for ($i = 0; $i < count($d_ids); $i++) {
					$geraete .= $d_ids[$i] . ", " . $names[$i] . "<br>";
				}
                echo $geraete;
            } else {
                echo "Keine Reservierungs-ID übermittelt.";
            }
        } else {
            echo "Ungültige Anfragemethode.";
        }
        ?>
        <br>
        <!-- Buttons -->
        <form method="POST" action="edit_reservation" style="display:inline;">
            <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
            <div class='col-md-6 mb-3'>
                <button type='submit' class='btn btn-success btn-block rounded mr-1 mb-1'>
                    <?php echo translate('word_editBig'); ?>
                </button>
            </div>
        </form>

    </div>
</body>

<?php
    echo $OUTPUT->footer();
?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
        // display current page in navbar
        const links = document.querySelectorAll('#navbarMenu .nav-link');
        const currentPath = window.location.pathname.toLowerCase()
            .replace(/^\/edurent\//, '')
            .replace(/\.php$/, '');

        links.forEach(link => {
            const linkPath = link.getAttribute('href').toLowerCase();

            if (currentPath == linkPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });

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

    //Select2 for user
    $(document).ready(function() {
        $('#user_select').select2({
            placeholder: "Person auswählen",
            allowClear: true
        });
    });

    //daterangepicker
    var first_day = new Date();
    first_day.setDate(first_day.getDate() + <?php echo $lead_time_days; ?>);

    var dd = String(first_day.getDate()).padStart(2, '0');
    var mm = String(first_day.getMonth() + 1).padStart(2, '0');
    var yyyy = first_day.getFullYear();
    first_day = dd + '/' + mm + '/' + yyyy;

    var last_day = new Date();
    last_day.setDate(last_day.getDate() + <?php echo $days_bookable_in_advance; ?> + <?php echo $lead_time_days; ?>);

    var dd = String(last_day.getDate()).padStart(2, '0');
    var mm = String(last_day.getMonth() + 1).padStart(2, '0');
    var yyyy = last_day.getFullYear();
    last_day = dd + '/' + mm + '/' + yyyy;

    $(function() {
        $('input[name="daterange"]').daterangepicker({
            //Daterangepicker gets opened
            "locale": {
                "format": "DD/MM/YYYY",
                "separator": " <?php echo translate('word_to'); ?> ",
                "applyLabel": "<?php echo translate('word_confirm'); ?>",
                "cancelLabel": "<?php echo translate('word_back'); ?>",
                "daysOfWeek": [
                    "<?php echo translate('weekday_short_7'); ?>",
                    "<?php echo translate('weekday_short_1'); ?>",
                    "<?php echo translate('weekday_short_2'); ?>",
                    "<?php echo translate('weekday_short_3'); ?>",
                    "<?php echo translate('weekday_short_4'); ?>",
                    "<?php echo translate('weekday_short_5'); ?>",
                    "<?php echo translate('weekday_short_6'); ?>"
                ],
                "monthNames": [
                    "<?php echo translate('word_month_1'); ?>",
                    "<?php echo translate('word_month_2'); ?>",
                    "<?php echo translate('word_month_3'); ?>",
                    "<?php echo translate('word_month_4'); ?>",
                    "<?php echo translate('word_month_5'); ?>",
                    "<?php echo translate('word_month_6'); ?>",
                    "<?php echo translate('word_month_7'); ?>",
                    "<?php echo translate('word_month_8'); ?>",
                    "<?php echo translate('word_month_9'); ?>",
                    "<?php echo translate('word_month_10'); ?>",
                    "<?php echo translate('word_month_11'); ?>",
                    "<?php echo translate('word_month_12'); ?>"
                ],
                "firstDay": 1
            },
            "startDate": first_day,
            "endDate": first_day,
            "minDate": first_day,
            "maxDate": last_day,
            "maxSpan": {
                "days": <?php echo $max_loan_duration; ?>
            },
            "opens": "center",
            "drops": "auto",
            isInvalidDate: function(date) {
                if (date.day() == 6 || date.day() == 0) //disable weekend
                    return true;
                return false;
            }
        });
    });

    $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
        document.getElementById("date_from").value = picker.startDate.format('DD-MM-YYYY');
        document.getElementById("date_to").value = picker.endDate.format('DD-MM-YYYY');
    });
</script>