<script>
    function get_department(device_type) {
        //hide all & uncheck
        var keys = Object.keys(device_type);
        for (let key of keys) {
            if(department_rentable[key] == undefined) continue; //skip departments without pickupday
            var keys2 = Object.keys(device_type[key]);
            for (let key2 of keys2) {
                var type = "t_" + key2;
                var checkb = "type_" + key2;
                if(document.getElementById(type)) document.getElementById(type).style.display = 'none';
                if (document.getElementById(checkb)) document.getElementById(checkb).checked = false;
            }
        }        

        //show department mail
        document.getElementById('mail-contact').innerHTML = "<?php echo translate('text_questionMail'); ?>";
        document.getElementById('mail-contact').innerHTML += "<br><a href='mailto:" + departments[document.getElementById("selected_department").value]['mail'] + "'>" + departments[document.getElementById("selected_department").value]['mail'] + "</bra>";
        document.getElementById('mail-contact').style.display = "block";

        //show devices of department
        var department = document.getElementById("selected_department").value;
        if (part_of_department[department]) { //if any type is rentable
            var keys = Object.keys(part_of_department[department]);
            for (let key of keys) {
                //show department
                var home_deparments = Object.keys(device_type);
                for (let home_deparment of home_deparments) {
                    var devices = Object.keys(device_type[home_deparment]);
                    for (let device of devices) {
                        if (device == part_of_department[department][key]) {
                            var depart = "d_" + home_deparment;
                            if (document.getElementById(depart)) {
                                document.getElementById(depart).style.display = 'table-row';
                            }
                        }
                    }
                }
                //show type
                var type = "t_" + part_of_department[department][key];
                if (document.getElementById(type)) {
                    document.getElementById(type).style.display = 'table-row';
                }
            }

            document.getElementById('step2').style.display = "block";
        }

        if (part_of_department[0]) { //if any type from all is rentable
            var keys = Object.keys(part_of_department[0]); //show from all
            for (let key of keys) {
                //show department
                var home_deparments = Object.keys(device_type);
                for (let home_deparment of home_deparments) {
                    var devices = Object.keys(device_type[home_deparment]);
                    for (let device of devices) {
                        if (device == part_of_department[0][key]) {
                            var depart = "d_" + home_deparment;
                            if (document.getElementById(depart)) {
                                document.getElementById(depart).style.display = 'table-row';
                            }
                        }
                    }
                }
                //show type
                var type = "t_" + part_of_department[0][key];
                if (document.getElementById(type)) {
                    document.getElementById(type).style.display = 'table-row';
                }
            }

            document.getElementById('step2').style.display = "block";
        }
    }
</script>

<?php
    function show_department_select(){
        global $lang;
        global $departments;
        global $unassigned_institute;
        global $all_institutes;
        global $link;
       
        //get name of selected department
        if (isset($_SESSION['selected_department'])) {
            $department_id = $_SESSION['selected_department'];

            $query = "SELECT department_de FROM departments WHERE department_id = ?";
            if ($stmt = mysqli_prepare($link, $query)) {
                mysqli_stmt_bind_param($stmt, "s", $department_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $department_name);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }            
        }

        echo "<h5 class='select' style='text-align:left;'><b>" . translate('text_step1') . "</b> <a href='#' data-toggle='tooltip' data-html='true' title='Sie können nur Geräte ausleihen, welche für ihr Institut freigegegen sind. Bitte wählen Sie jenes aus, dem Sie angehören.'><?php echo htmlspecialchars_decode('&#9432;'); ?></a></h5>";
        echo "<select class='form-select' name='selected_department' id='selected_department' onchange='get_department(device_type)' style='width:100%; max-width: 40ch; margin: 0 auto;'>";
            if(isset($department_name)) echo "<option selected disabled hidden value=''>" . $department_name . "</option>"; //show selected department
            else echo "<option selected disabled hidden value=''>" . translate('word_none2') . "</option>"; //show none selected
            for ($i = 0; $i < count(array_keys($departments)); $i++) {
                if (array_keys($departments)[$i] != $unassigned_institute && array_keys($departments)[$i] != $all_institutes) echo "<option value='" . array_keys($departments)[$i] . "'>" . $departments[array_keys($departments)[$i]][get_language()] . "</option>"; //if can rent any device + all
            }
        echo "</select>";
        echo "<p id='mail-contact' style='display:none;'>Text text</p>";
    }

    function show_device_select($device_type, $department_rentable, $departments){
        echo "<h5 class='select' style='text-align:left;'><b>" . translate('text_step2') . "</b></h5>";
        echo "<br>";
        echo "<div class='table-responsive'>";
            echo "<table class='table table-condensed'>";
                echo "<tbody>";
                    //for all departments
                    $all_departments = array_keys($device_type);
                    for ($i = 0; $i < count($all_departments); $i++) {
                        if (!isset($department_rentable[$all_departments[$i]])) continue; //skip departments without pickupday
                        //department name
                        echo "<tr id='d_" . $all_departments[$i] . "'>";
                            echo "<td class='band' colspan='5'>";
                                echo "<b class='band'>" . $departments[$all_departments[$i]][get_language()] . "</b>";
                                echo "<input class='form-check-input' type='hidden' id='department_" . $all_departments[$i] . "' name='department_" . $all_departments[$i]. "' value='1'>";
                            echo "</td>";
                        echo "</tr>";
                        
                        //for all devices of department
                        $available = 0;

                        for ($o = 0; $o < count($device_type[$all_departments[$i]]); $o++) {
                            $type = $device_type[$all_departments[$i]][array_keys($device_type[$all_departments[$i]])[$o]];
                            $id = array_keys($device_type[$all_departments[$i]])[$o];
                            if($available == 0) $available = 1;

                            // display every device + infos
                            echo "<tr id='t_" . $id . " style='display:none;'>";
                                echo "<td style='text-align:center; vertical-align:middle;'>";
                                    echo "<input type='checkbox' id='type_" . $id . "' name='type_" . $id . "' value='" . $all_departments[$i] . "'>";
                                    echo "</td>";

                                    echo "<td style='width: 11ch; text-align:center; vertical-align:middle;'>";
                                    if ($type['img_path']) {
                                        echo "<img src='" . $type['img_path'] . "' style='width: 100%'>";
                                    }
                                    echo "</td>";

                                    echo "<td style='text-align:center; vertical-align:middle;'>";
                                        echo $type['name'];
                                    echo "</td>";

                                    echo "<td style='text-align:center; vertical-align:middle;'>";
                                    $info = $type['info'];
                                    //if tooltip exists
                                    if (strlen(strip_tags($type['tooltip'])) > 0) $info .= "  <a href='#' data-toggle='tooltip' data-html='true' title='" . $type['tooltip'] . "'>" . htmlspecialchars_decode("&#9432;") . "</a>";
                                    echo $info;
                                    echo "</td>";

                                    echo "<td style='text-align:center; vertical-align:middle;'>";
                                    if($type['max_loan_days'] > 1){
                                        echo translate('text_rentMultipleDays', ['a' => $type['max_loan_days']]); 
                                    } else {
                                        echo translate('text_rentSingleDay'); 
                                    }
                                echo "</td>";
                            echo "</tr>";
                        }
                        if($available == 0) echo "<tr id=t_" . array_keys($device_type[$all_departments[$i]])[0] . " style='display:none;'><td colspan='5' style='text-align:center;'><i>Keine Geräte verfügbar</i></td></tr>";
                    }
                echo "</tbody>";
            echo "</table>";
        echo "</div>";
        echo "<div class='errormessage' style='color:red; display:none;'>";
            echo "Bitte wählen Sie die benötigten Geräte aus der Liste aus.";
        echo "</div>";
    }