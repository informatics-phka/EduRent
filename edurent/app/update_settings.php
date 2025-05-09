<!DOCTYPE html>
<?php
check_is_admin($user_username);

$is_superadmin = is_superadmin($user_username);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (
        isset($_POST['days_bookable_in_advance']) &&
        isset($_POST['lead_time_days']) &&
        isset($_POST['max_loan_duration'])
    ) {
        $days_bookable_in_advance = $_POST['days_bookable_in_advance'];
        $lead_time_days = $_POST['lead_time_days'];
        $max_loan_duration = $_POST['max_loan_duration'];
        $url = $_GET['url'] ?? 'index';
        $debug = $_POST['debug'] == 'on' ? 1 : 0;

        $sql = "UPDATE server SET days_bookable_in_advance=?, lead_time_days=?, max_loan_duration=?, debug=? WHERE id=1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiii", $days_bookable_in_advance, $lead_time_days, $max_loan_duration, $debug);
            if (mysqli_stmt_execute($stmt)) {
                $SESSION->toasttext = "Die Einstellungen wurden aktualisiert";
                echo "<script>window.location.href = 'update_settings';</script>";
                exit();
            } else {
                save_in_logs("Error updating settings: " . $stmt->error);
            }
        } else save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
        $stmt->close();
    } else {
        save_in_logs("All fields are required");
    }
}

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
?>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- stylesheet -->
    <link rel="stylesheet" href="style-css/rent.css">
    <link rel="stylesheet" href="style-css/toasty.css">
    <link rel="stylesheet" href="style-css/accessability.css">
    <link rel="stylesheet" href="style-css/navbar.css">

    <!-- Toast -->
    <?php require_once("Controller/toast.php"); ?>
</head>
<body>
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
        <form action="update_settings.php" method="post">
            <div class="mb-3">
                <label for="days_bookable_in_advance" class="form-label"><?php echo translate('text_daysBookableInAdvance'); ?>:</label>
                <input type="number" class="form-control" id="days_bookable_in_advance" name="days_bookable_in_advance" value="<?php echo $days_bookable_in_advance; ?>">
            </div>

            <div class="mb-3">
                <label for="lead_time_days" class="form-label"><?php echo translate('text_leadTimeDays'); ?>:</label>
                <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" value="<?php echo $lead_time_days; ?>">
            </div>

            <div class="mb-3">
                <label for="max_loan_duration" class="form-label"><?php echo translate('text_maxLoanDureation'); ?>:</label>
                <input type="number" class="form-control" id="max_loan_duration" name="max_loan_duration" value="<?php echo $max_loan_duration; ?>">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="debug" name="debug" <?php if($debug) echo "checked"; ?>>
                <label class="form-check-label" for="debug"><?php echo translate('word_serviceMode'); ?></label>
            </div>

            <div class='row justify-content-center'>
                <div class='col-md-6 mb-3'>
                    <button type='submit' id="submit" class='btn btn-success btn-block rounded mr-1 mb-1'>
                        <i class="fas fa-save mr-2"></i>
                        <?php echo translate('word_save'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</body>
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
</script>
</html>
<?php
echo $OUTPUT->footer();
?>