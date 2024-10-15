<?php
    echo '<button onclick="topFunction()" id="myBtn" class="btn btn-primary rounded-circle d-none" title="Go to top">';
        echo '<i class="fas fa-arrow-up"></i>';
    echo '</button>';
?>

<style>
    #myBtn {
        position: fixed;
        bottom: 50%;
        right: 20px;
        transition: opacity 0.3s ease;
        transform: translateY(50%);
        z-index: 99;
    }

    #myBtn:hover {
        opacity: 0.8;
    }
</style>

<script>
    let mybutton = document.getElementById("myBtn");
    window.onscroll = function() {scrollFunction()};

    function scrollFunction() {
        if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
            mybutton.classList.remove("d-none");
            mybutton.classList.add("d-block");
        } else {
            mybutton.classList.remove("d-block");
            mybutton.classList.add("d-none");
        }
    }

    function topFunction() {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    }
</script>