<?php
    class Rules
    {
        
        function __construct($title, ...$texts)
        {
            echo '<div id="accordion">';
			    echo '<div class="accordion w-100" id="basicAccordion">';
                    echo '<div class="accordion-item" id="heading1">';
                        echo '<h5 class="accordion-header">';
                            echo '<button class="accordion-button collapsed" type="button" data-toggle="collapse" data-target="#collapse1" aria-expanded="false" aria-controls="collapse1">';
                                echo $title;
                            echo '</button>';
                        echo '</h5>';
                        echo '<div id="collapse1" class="collapse" aria-labelledby="heading1" data-parent="#accordion">';
                            echo '<div class="card-body">';
                                if(count($texts) > 1){
                                    echo "<ul>";
                                    foreach ($texts as $text) {
                                        echo '<li class="mb-2">' . $text . '</li>';
                                    }
                                    echo "</ul>";
                                }
                                else{
                                    echo $texts[0];
                                }
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        }
    }