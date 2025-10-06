<!DOCTYPE HTML>
<?php
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

check_superadmin($user_username);
$is_superadmin = is_superadmin($user_username);

// Check lang files for errors
$langDir = __DIR__ . '/../lang';
$files = glob($langDir . '/*.php');

foreach ($files as $file) {
    $output = null;
    $returnVar = null;
    exec("php -l " . escapeshellarg($file), $output, $returnVar);

    if ($returnVar !== 0) {
        save_in_logs("ERROR: Syntaxfehler in " . basename($file));
        continue;
    }

    $translations = null;
    include $file;

    if (!isset($translations) || !is_array($translations)) {
        save_in_logs("ERROR: translations ist nicht definiert oder kein Array in " . basename($file));
        continue;
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
<?php
echo $OUTPUT->footer();
?>
