<?php 
    $month = date('m');

    /*if($month == 2 || $month == 3 || $month == 8 || $month == 9){   
        echo "Es sind grade Semesterferien";
    }
    else{
        echo "Es sind grade keine Semesterferien";
    }*/
    if($debug){   
        echo '<div id="error-message" class="alert alert-warning alert-dismissible fade show text-center" role="alert" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000;">';
            echo '<strong>' . translate('text_maintenance') . '</strong>';
            echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            echo '<span aria-hidden="true">&times;</span>';
            echo '</button>';
        echo '</div>';
        echo '<script>';
        echo 'var headerHeight = document.querySelector(".navbar").offsetHeight;';
        echo 'document.getElementById("error-message").style.marginTop = headerHeight + "px";';
        echo 'document.getElementById("error-message").style.display = "block";';
        echo '</script>;';
    }