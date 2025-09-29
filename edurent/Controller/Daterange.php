<?php function show_daterangepicker($is_superadmin){
    global $lang;
    global $lead_time_days;
    global $days_bookable_in_advance;
    global $max_loan_duration;
    global $link;

    // get max_loan_duration of department if selected_department is set in session
    if (isset($_SESSION['selected_department'])) {
        $department_id = $_SESSION['selected_department'];

        $query = "SELECT max_loan_duration FROM department_settings WHERE department_id = ?";
        if ($stmt = mysqli_prepare($link, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $department_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $max_loan_duration_department);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }    
        $max_loan_duration = $max_loan_duration_department;
    }

    if($is_superadmin)
    {
        $lead_time_days = 0;
        $days_bookable_in_advance = 365;
        $max_loan_duration = 365;
    }
        

    
?>

<!-- JQuery for daterangepicker -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<!-- Moment for daterangepicker -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<!-- daterangepicker -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<h5 class='select' style='text-align:left;'><b><?php echo translate('text_step3');?></b></h5>
<br>
<input type='text' name='daterange' id='daterange' style='text-align:center; width:100%; max-width: 27ch;' placeholder='Keine Reservierung möglich' /> <a href='#' data-toggle='tooltip' data-html='true' title='Eine Reservierung ist nur eine Woche im Voraus möglich.'><?php echo htmlspecialchars_decode("&#9432;"); ?></a>
<br>
<br>

<input type='hidden' id='date_from' name='date_from' value=''>
<input type='hidden' id='date_to' name='date_to' value=''>

<script>
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
        document.getElementById("submit_button").disabled = false;
    });
</script>

<?php 
}
?>