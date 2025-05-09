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
    $_SESSION['reservation_id'] = $reservation_id; // speichern
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
		
		<!-- Font Awesome -->
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
		
		<!-- Toast -->
		<?php require_once("Controller/toast.php"); ?>
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
		// display current header
		const links = document.querySelectorAll('#navbarMenu .nav-link');
        const currentPath = window.location.pathname.toLowerCase()
            .replace(/\.php$/, '');

		links.forEach(link => {
			const linkPath = link.getAttribute('href').toLowerCase();

			if (currentPath.endsWith(linkPath)) {
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
</script>