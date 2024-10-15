<?php
$number = 0;

//Toast on load
if(obj_exists_and_not_empty('toasttext', $SESSION)){
    sendToast($SESSION->toasttext);
    unset($SESSION->toasttext);
}


//toast on funtion
function sendToast($text)
{
    global $number;
    $name = "Toast_" . $number;
    $pos_top = 80 + $number * 100;
?>

    <div class='toast-container'>
        <div class='toast' id='<?php echo $name; ?>' role='alert' aria-live='assertive' aria-atomic='true' style='position: fixed; top: <?php echo $pos_top; ?>px; right: 20px;'>
            <div class='toast-header'>
                <?php echo $text; ?>
            </div>
        </div>
    </div>

    <script>
        var option = {
            animation: true,
            delay: 5000
        };

        var toastHTMLElement = document.getElementById('<?php echo $name; ?>');
        var toastElement = new bootstrap.Toast(toastHTMLElement, option);
        toastElement.show();
    </script>

<?php
    $number++;
}
?>