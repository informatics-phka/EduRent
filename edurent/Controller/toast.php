<?php
// counter for toasts
if (!isset($GLOBALS['toast_number'])) {
    $GLOBALS['toast_number'] = 0;
}

// Toast on load
if (isset($SESSION->toasttext) && !empty($SESSION->toasttext)) {
    sendToast($SESSION->toasttext);
    unset($SESSION->toasttext);
}

// toast on function
function sendToast($text)
{
    $number = $GLOBALS['toast_number'];
    $name = "Toast_" . $number;
    $pos_top = 80 + $number * 100; // Abstand nach oben für gestapelte Toasts
    ?>
    <div class='toast-container'>
        <div class='toast' id='<?php echo $name; ?>' role='alert' aria-live='assertive' aria-atomic='true'
             style='position: fixed; top: <?php echo $pos_top; ?>px; right: 20px; z-index:2000;'>
            <div class='toast-header'>
                <?php echo $text; ?>
            </div>
        </div>
    </div>

    <script>        
        var toastHTMLElement = document.getElementById('<?php echo $name; ?>');
        var toastElement = new bootstrap.Toast(toastHTMLElement, { animation: true, delay: 5000 });
        toastElement.show();
    </script>
    <?php    
    $GLOBALS['toast_number']++;
    ?>
    <script>
        // Reset counter after 5.5 seconds if no new toast is created
        setTimeout(() => {
            if (document.querySelectorAll('.toast').length === 0) {
                <?php $GLOBALS['toast_number'] = 0; ?>
            }
        }, 5500);
    </script>
    <?php
}
?>

<!-- JS-toast-function -->
<script>
let toastCount = <?php echo $GLOBALS['toast_number']; ?>;

function showToast(text) {
    const name = "Toast_" + toastCount;
    const posTop = 80 + toastCount * 100;

    const container = document.createElement('div');
    container.className = 'toast-container';
    container.innerHTML = `
        <div class='toast' id='${name}' role='alert' aria-live='assertive' aria-atomic='true'
             style='position: fixed; top: ${posTop}px; right: 20px; z-index:2000;'>
            <div class='toast-header'>
                ${text}
            </div>
        </div>
    `;
    document.body.appendChild(container);

    var toastHTMLElement = document.getElementById(name);
    var toastElement = new bootstrap.Toast(toastHTMLElement, { animation: true, delay: 5000 });
    toastElement.show();

    toastCount++;

    // reset counter after 6 seconds if no new toast is created
    setTimeout(() => {
        if (document.querySelectorAll('.toast').length === 0) {
            toastCount = 0;
        }
    }, 6000);
}
</script>