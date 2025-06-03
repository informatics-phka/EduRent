<!DOCTYPE HTML>
<?php
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

check_superadmin($user_username);
$is_superadmin = is_superadmin($user_username);
?>

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
?>

<html lang="en">
<head>
	<meta charset="UTF-8">
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
    <link rel="stylesheet" href="style-css/logs.css">
    <link rel="stylesheet" href="style-css/navbar.css">
    
    <!-- -->
    <link rel="stylesheet" href="style-css/ahover.css">
    <script src="js/clickablerow.js"></script>
    
    <!-- Toast -->
    <?php require_once("Controller/toast.php"); ?>
</head>

<?php
    if (exists_and_not_empty('delete', $_GET)) {
        unlink($_GET['delete']);
        $SESSION->toasttext = "Die Log-Datei wurde gelöscht";
        echo "<script>window.location.href = 'logs';</script>";
        exit();
    }
?>

<body>
	<div class="main">
        <?php require_once 'navbar.php'; ?>	
        <br>

        <?php
        $files = scandir('./log');
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            if (preg_match('/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/', $file, $matches)) {
                $displayDate = $matches[3] . '.' . $matches[2] . '.' . substr($matches[1], 2); // DD.MM.YY
            } else {
                $displayDate = $file;
            }
        ?>
            <a href='view_logs.php?file=<?php echo $file; ?>' class="log-link">
            <?php echo htmlspecialchars($displayDate); ?>
            </a>
        <?php } ?>
    </div>
</body>
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
</script>
<?php
echo $OUTPUT->footer();
?>
