<!DOCTYPE html>
<?php 
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
                echo "<script>window.location.href = 'update_settings.php';</script>";
            } else {
                save_in_logs("Error updating settings: " . $stmt->error);
            }
        } else save_in_logs("ERROR: Could not prepare statement. " . mysqli_error($link));
        $stmt->close();
    } else {
        save_in_logs("All fields are required");
    }
}
?>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!-- Bootstrap -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="main">
        <h2>Server Einstellungen</h2>
        <form action="update_settings.php" method="post">
            <div class="mb-3">
                <label for="days_bookable_in_advance" class="form-label">Days Bookable in Advance:</label>
                <input type="number" class="form-control" id="days_bookable_in_advance" name="days_bookable_in_advance" value="<?php echo $days_bookable_in_advance; ?>">
            </div>

            <div class="mb-3">
                <label for="lead_time_days" class="form-label">Lead Time Days:</label>
                <input type="number" class="form-control" id="lead_time_days" name="lead_time_days" value="<?php echo $lead_time_days; ?>">
            </div>

            <div class="mb-3">
                <label for="max_loan_duration" class="form-label">Max Loan Duration:</label>
                <input type="number" class="form-control" id="max_loan_duration" name="max_loan_duration" value="<?php echo $max_loan_duration; ?>">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="debug" name="debug" <?php if($debug) echo "checked"; ?>>
                <label class="form-check-label" for="debug">Debug Mode</label>
            </div>

            <div class='row justify-content-center'>
                <div class='col-md-6 mb-3'>
                    <a class='btn btn-secondary btn-block' href='admini.php'>
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
    </div>
</body>
</html>
<?php
echo $OUTPUT->footer();
?>