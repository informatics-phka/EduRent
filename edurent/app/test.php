<!DOCTYPE html>
<html lang="de">
<?php
  //get all d_id with a defined rent day
  $result = mysqli_query($link, "SELECT DISTINCT d_id FROM rent_days");
  $departmentsWithRentDays = [];
  while ($row = mysqli_fetch_assoc($result)) {
      $departmentsWithRentDays[] = intval($row['d_id']);
  }


echo "<script>const departmentsWithRentDays = " . json_encode($departmentsWithRentDays) . ";</script>";
?>  
<script>
    // Fallback, if PHP did not set the variable
    if (typeof departmentsWithRentDays === "undefined") {
      const departmentsWithRentDays = window.departmentsWithRentDays || [];
    }    
    
  </script>
<?php
//get user_id
$user_id = get_user_id($user_username, $admin_mail, $lang);
//if ($user_id == -1) {
//    throw new Exception("Found no User");
//}


//handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation'])) {
  save_reservation($_POST, $link, $user_id);
  echo "success";
  exit;
}


//get rent time for save_reservation
function get_rent_time_for_department($link, $department_id, $date) {
    $weekday = date('N', strtotime($date));
    $sql = "SELECT time FROM rent_days WHERE d_id = ? AND dayofweek = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $department_id, $weekday);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);


        if ($row && isset($row['time'])) {
            //format "HH:MM-HH:MM"
            $times = explode('-', $row['time']);
            $start = isset($times[0]) ? trim($times[0]) : '08:00:00';
            $end = isset($times[1]) ? trim($times[1]) : null;
            return [
                'start' => $start,
                'end' => $end
            ];
        }
    }
    //default times
    return [
        'start' => '08:00:00',
        'end' => '20:00:00'
    ];
}


//get room for save_reservation
function get_room_by_department($link, $department_id) {
    $sql = "SELECT room FROM departments WHERE department_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $department_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        mysqli_stmt_close($stmt);
        if ($row && isset($row['room'])) {
            return $row['room'];
        }
    }
    return null;
}


  //save reservation data
function save_reservation($post_data, $link, $user_id) {
    $missing = [];
    if (empty($post_data['department'])) $missing[] = 'Abteilung (department)';
    if (empty($post_data['device_ids']) || !is_array($post_data['device_ids'])) $missing[] = 'Geräteauswahl (device_ids)';
    if (count($missing) > 0) {
        echo "Fehlende Eingaben: " . implode(', ', $missing);
        return;
    }

    $department = intval($post_data['department']);
    $device_type_ids = array_map('intval', $post_data['device_ids']);
    
    $date_froms = $post_data['date_froms'] ?? [];
    $date_tos = $post_data['date_tos'] ?? [];
    
    $devices_grouped_by_home_dept = [];

    foreach ($device_type_ids as $device_type_id) {
        $sqlHomeDept = "SELECT dt.home_department
                        FROM device_type dt
                        WHERE dt.device_type_id = ?";
        $stmtHome = mysqli_prepare($link, $sqlHomeDept);
        mysqli_stmt_bind_param($stmtHome, "i", $device_type_id);
        mysqli_stmt_execute($stmtHome);
        $resHome = mysqli_stmt_get_result($stmtHome);
        $rowHome = mysqli_fetch_assoc($resHome);
        mysqli_stmt_close($stmtHome);


        if ($rowHome && isset($rowHome['home_department'])) {
            $home_dept = intval($rowHome['home_department']);
        } else {
            echo "Fehler: Konnte home_department für device_type_id " . htmlspecialchars($device_type_id) . " nicht finden.<br>";
            continue;
        }

        $devices_grouped_by_home_dept[$home_dept][] = $device_type_id;
    }


    //1 Reservation for each home_department group
    foreach ($devices_grouped_by_home_dept as $home_dept => $device_type_ids_group) {

        $date_from_group = $date_froms[$home_dept] ?? reset($date_froms) ?? null;
        $date_to_group = $date_tos[$home_dept] ?? reset($date_tos) ?? null;

        if (!$date_from_group || !$date_to_group) {
            echo "Fehlende Zeitangabe für Abteilung $home_dept";
            continue;
        }

        $time_from_array = get_rent_time_for_department($link, $home_dept, $date_from_group);
        $time_to_array = get_rent_time_for_department($link, $home_dept, $date_to_group);

        $time_from = $time_from_array['start'] ?? '08:00:00';
        $time_to = $time_to_array['end'] ?? '20:00:00';

        $room_from = get_room_by_department($link, $home_dept);
        $room_to = get_room_by_department($link, $home_dept);

        //SQL Insert reservation
        $sql = "INSERT INTO reservations 
                (department_id, status, user_id, orga, date_from, date_to, time_from, time_to, room_from, room_to) 
                VALUES (?, 1, ?, '', ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        if (!$stmt) {
            echo "Reservierung konnte nicht vorbereitet werden: " . mysqli_error($link);
            continue;
        }
        mysqli_stmt_bind_param($stmt, "iissssss", $home_dept, $user_id, $date_from_group, $date_to_group, $time_from, $time_to, $room_from, $room_to);
        if (!mysqli_stmt_execute($stmt)) {
            echo "Reservierung konnte nicht gespeichert werden: " . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            continue;
        }


        $reservation_id = mysqli_insert_id($link);
        if (!$reservation_id) {
            echo "Fehler: Ungültige reservation_id";
            mysqli_stmt_close($stmt);
            continue;
        }
        mysqli_stmt_close($stmt);


        //get device_ids for device_type_ids
        $actual_device_ids = [];
        foreach ($device_type_ids_group as $type_id) {
            $sqlGetDeviceId = "SELECT device_id FROM device_list WHERE device_type_id = ? LIMIT 1";
            $stmtGet = mysqli_prepare($link, $sqlGetDeviceId);
            if (!$stmtGet) {
                echo "Fehler: Geräteabfrage konnte nicht vorbereitet werden: " . mysqli_error($link);
                continue;
            }
            mysqli_stmt_bind_param($stmtGet, "i", $type_id);
            mysqli_stmt_execute($stmtGet);
            $resGet = mysqli_stmt_get_result($stmtGet);
            if ($rowGet = mysqli_fetch_assoc($resGet)) {
                $actual_device_ids[] = intval($rowGet['device_id']);
            } else {
                echo "Kein Gerät gefunden für device_type_id: " . htmlspecialchars($type_id) . "<br>";
            }
            mysqli_stmt_close($stmtGet);
        }


        //insert devices_of_reservations entries
        $sqlDev = "INSERT INTO devices_of_reservations (reservation_id, device_id) VALUES (?, ?)";
        $stmtDev = mysqli_prepare($link, $sqlDev);
        if (!$stmtDev) {
            echo "Geräte konnten nicht vorbereitet werden: " . mysqli_error($link);
            continue;
        }
        foreach ($actual_device_ids as $device_id) {
            mysqli_stmt_bind_param($stmtDev, "ii", $reservation_id, $device_id);
            if (!mysqli_stmt_execute($stmtDev)) {
                echo "Gerät konnte nicht gespeichert werden (device_id=" . htmlspecialchars($device_id) . "): " . mysqli_stmt_error($stmtDev);
            }
        }
        mysqli_stmt_close($stmtDev);
    }
}

  //get all departments
  function load_departments() {
      global $link;
      global $departments;
      $sql = "SELECT department_id, department_de FROM departments ORDER BY department_id ASC";
      $departments = [];
      if ($stmt = mysqli_prepare($link, $sql)) {
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);
          $departments = mysqli_fetch_all($res, MYSQLI_ASSOC);
          mysqli_free_result($res);
          mysqli_stmt_close($stmt);
      }
  }


  //get all device types
  function load_device_types() {
      global $link;
      global $device_types;
      $sql = "SELECT * FROM device_type ORDER BY device_type_id ASC";
      $device_types = [];
      if ($stmt = mysqli_prepare($link, $sql)) {
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);
          $device_types = mysqli_fetch_all($res, MYSQLI_ASSOC);
          mysqli_free_result($res);
          mysqli_stmt_close($stmt);
      }
      
      //filter device types by allowed departments
      foreach ($device_types as &$device) {
          $device['departmentsAllowed'] = [];
          $tid = intval($device['device_type_id']);
          $sql = "SELECT department_id FROM type_department WHERE type_id = ?";
          if ($stmt = mysqli_prepare($link, $sql)) {
              mysqli_stmt_bind_param($stmt, "i", $tid);
              mysqli_stmt_execute($stmt);
              $res = mysqli_stmt_get_result($stmt);
              while($row = mysqli_fetch_assoc($res)) {
                  $device['departmentsAllowed'][] = intval($row['department_id']);
              }
              mysqli_free_result($res);
              mysqli_stmt_close($stmt);
          }
      }
  }


  //get rent days by department id
  function getRentDaysByDepartmentId($d_id) {
      global $link;
      $rent_days = [];
      $sql = "SELECT time, dayofweek FROM rent_days WHERE d_id = ?";
      if ($stmt = mysqli_prepare($link, $sql)) {
          mysqli_stmt_bind_param($stmt, "i", $d_id);
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);
          $rent_days = mysqli_fetch_all($res, MYSQLI_ASSOC);
          mysqli_free_result($res);
          mysqli_stmt_close($stmt);
      }
      return $rent_days;
  }


  //load initial data
  load_departments();
  load_device_types();
?>


<head>  
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!--<title></title>-->
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">


  <!-- Bootstrap Bundle JS -->
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- local version <script src="js/bootstrap.bundle.min.js"></script> -->


  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />


  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">


  <style>
    body { font-family: Arial, sans-serif; }
    .step {
      display: none;
    }
    .step.active {
      display: block;
    }
    
    .nav-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      font-size: 2rem;
      cursor: pointer;
      z-index: 10;
    }
    .nav-left {
      left: -2rem;
    }
    .nav-right {
      right: -2rem;
    }
  </style>
</head>


<body>
  <div class="container my-4">
    <h2 class="text-center mb-4">Wilkommen bei Edurent</h2>
    <form id="bestellForm" method="post">
      <div id="orderCarousel" class="carousel slide" data-bs-interval="false">
        <div class="carousel-inner">

          <!-- Step 1: choose department-->
          <div class="carousel-item active">
            <div class="card p-4">
              <h4>1. Department wählen</h4>
              <select id="departmentSelect" name="department" class="form-select" required>
                <option value="" selected disabled hidden>
                  Bitte Institut auswählen
                </option>
                <?php foreach ($departments as $dep):
                  //skip invalid departments
                  if (in_array($dep['department_id'], [0, -1]) || empty($dep['department_id'])) continue;
                ?>
                <option value="<?php echo htmlspecialchars($dep['department_id']); ?>">
                  <?php echo htmlspecialchars($dep['department_de']); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-primary" id="toDevices" disabled>Weiter <i class="bi bi-chevron-compact-right"></i></button>
              </div>
            </div>
          </div>

          <!-- Step 2: choose devices -->
          <div class="carousel-item">
            <div class="card p-4">
              <h4>2. Geräte auswählen</h4>
              <div id="deviceListContainer" class="mb-4"></div>
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-target="#orderCarousel" data-bs-slide="prev">
                  <i class="bi bi-chevron-compact-left"></i> Zurück
                </button>
                <button type="button" class="btn btn-primary" id="toDatepicker" disabled>
                  Weiter <i class="bi bi-chevron-compact-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Step 3: choose pickup and return days -->
          <div class="carousel-item">
            <div class="card p-4">
              <h4>3. Zeitspanne wählen</h4>
              <div id="datePickersContainer" class="mb-4"></div>

              <!-- Hidden fields for reservation data -->
              <div id="reservationHiddenFields"></div>

              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-target="#orderCarousel" data-bs-slide="prev">
                  <i class="bi bi-chevron-compact-left"></i> Zurück
                </button>
                <button type="submit" name="reservation" class="btn btn-success">
                  Bestellung abschließen
                </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <script>
      //convert PHP array $device_types to JavaScript object for device type information
      const deviceTypes = <?php echo json_encode($device_types); ?>;

      //initialize empty object to store rent day information per department
      const rentDays = {};

      //populate rentDays with PHP data for each department dynamically
      <?php
      foreach ($departments as $dep) {
        $rid = intval($dep['department_id']);
        $rdays = getRentDaysByDepartmentId($rid);
        echo "rentDays[$rid] = " . json_encode($rdays) . ";\n";
      }
      ?>

      //initialize Bootstrap carousel on the order form; disable auto-rotate
      const carouselElement = document.querySelector('#orderCarousel');
      const carousel = new bootstrap.Carousel(carouselElement, { interval: false });

      //store references to important DOM elements for later interactions
      const departmentSelect = document.getElementById('departmentSelect');
      const toDevicesBtn = document.getElementById('toDevices');
      const deviceListContainer = document.getElementById('deviceListContainer');
      const toDatepickerBtn = document.getElementById('toDatepicker');
      const datePickersContainer = document.getElementById('datePickersContainer');

      //variables to hold user selections through the form steps
      let selectedDepartment = null;
      let selectedDevices = [];
      let selectedStartDate = '';
      let selectedEndDate = '';

      //when "Next" button in step 2 is clicked, render the date pickers for step 3
      toDatepickerBtn.addEventListener('click', () => {
        renderDatePickers();
        carousel.next();
      });


      //function to create/update hidden fields with selected devices and dates for form submission
      function updateReservationHiddenFields() {
        const hiddenContainer = document.getElementById('reservationHiddenFields');
        hiddenContainer.innerHTML = '';


        //add hidden inputs for each selected device's ID
        selectedDevices.forEach(dev => {
          let field = document.createElement('input');
          field.type = 'hidden';
          field.name = 'device_ids[]';
          field.value = dev.device_type_id;
          hiddenContainer.appendChild(field);
        });


        //individual start and end dates per institute
        const grouped = selectedDevices.reduce((acc, device) => {
          const hd = device.home_department;
          if (!acc[hd]) acc[hd] = [];
          acc[hd].push(device);
          return acc;
        }, {});


        for (const hd in grouped) {
          const startInput = document.querySelector(`#startDate-${hd}`);
          const endInput = document.querySelector(`#endDate-${hd}`);
          if (startInput && endInput) {
            const startField = document.createElement('input');
            startField.type = 'hidden';
            startField.name = `date_froms[${hd}]`;
            startField.value = startInput.value;
            hiddenContainer.appendChild(startField);

            const endField = document.createElement('input');
            endField.type = 'hidden';
            endField.name = `date_tos[${hd}]`;
            endField.value = endInput.value;
            hiddenContainer.appendChild(endField);
          }
        }
      }

      //update hidden fields immediately when any date picker value changes
      datePickersContainer.addEventListener('change', updateReservationHiddenFields);

      //create a mapping of department IDs to their names using PHP data for display purposes
      const departmentsMap = {};
      <?php foreach($departments as $dep): ?>
        departmentsMap[<?php echo json_encode($dep['department_id']); ?>] = <?php echo json_encode($dep['department_de']); ?>;
      <?php endforeach; ?>
      departmentsMap[0] = "Für alle Institute";

      //enable the next button when a department is selected
      departmentSelect.addEventListener('change', () => {
        selectedDepartment = departmentSelect.value;
        toDevicesBtn.disabled = !selectedDepartment;
      });

      //when user clicks next after selecting department, load devices and move carousel forward
      toDevicesBtn.addEventListener('click', () => {
        loadDevices(selectedDepartment);
        carousel.next();
      });

      //load and render the list of selectable devices for the chosen department
      function loadDevices(departmentId) {
        deviceListContainer.innerHTML = '';
        selectedDevices = [];

        const deptIdInt = parseInt(departmentId);

        //filter device types by allowed departments and rental availability
        const filteredDevices = deviceTypes.filter(dev => {
          if (!dev.departmentsAllowed) return false;
          if (dev.departmentsAllowed.includes(-1)) return false;
          if (!departmentsWithRentDays.includes(Number(dev.home_department))) return false;


          if (dev.departmentsAllowed.includes(0)) return true;
          return dev.departmentsAllowed.includes(deptIdInt);
        });

        //show message if no devices available and disable next step button
        if (filteredDevices.length === 0) {
          deviceListContainer.innerHTML = '<p>Keine Geräte verfügbar.</p>';
          toDatepickerBtn.disabled = true;
          return;
        }

        //group devices by home department for display
        const grouped = filteredDevices.reduce((acc, dev) => {
          if (!acc[dev.home_department]) acc[dev.home_department] = [];
          acc[dev.home_department].push(dev);
          return acc;
        }, {});

        //build HTML table for device selection grouped by department
        let html = '';
        html += `<table class="table">
          <thead>
            <tr>
              <th></th>
              <th>Bild</th>
              <th>Gerätename</th>
              <th>Beschreibung</th>
              <th>Zeitraum</th>
            </tr>
          </thead>
          <tbody>`;

        for (const homeDept in grouped) {
          const group = grouped[homeDept];
          const groupName = (departmentsMap.hasOwnProperty(homeDept)) ? departmentsMap[homeDept] : `Institut ${homeDept}`;

          //department header row
          html += `<tr><th colspan="5" class="bg-info text-white">${groupName}</th></tr>`;

          //device rows with checkboxes for selection; ignoring invalid device names
          group.forEach(dev => {
            if (!dev.device_type_name || dev.device_type_name === "null") return;
            html += '<tr>';
            html += '<td colspan="5">';
            html += `<label style="width:100%;cursor:pointer;display:flex;align-items:center;">`;
            html += `<input type="checkbox" class="device-checkbox me-3" data-device='${JSON.stringify(dev)}' style="flex-shrink:0;">`;
            html += `<img src="${dev.device_type_img_path || ''}" alt="${dev.device_type_name}" style="width:80px;margin-right:1rem;">`;
            html += `<strong style="flex-grow:1;">${dev.device_type_name}</strong>`;
            html += `<span style="margin-left:auto;">${dev.device_type_info || ''}</span>`;
            html += `<span style="margin-left:2rem;">Für 1-${dev.max_loan_days} Tage ausleihen</span>`;
            html += `</label>`;
            html += '</td>';
            html += '</tr>';
            });
        }
        html += '</tbody></table>';

        //insert the generated HTML into the device list container
        deviceListContainer.innerHTML = html;

        //add event listeners to checkboxes updating selected devices array and enabling next button
        const checkboxes = deviceListContainer.querySelectorAll('.device-checkbox');
        checkboxes.forEach(chk => {
          chk.addEventListener('change', () => {
            const devData = JSON.parse(chk.getAttribute('data-device'));
            if (chk.checked) {
              selectedDevices.push(devData);
            } else {
              selectedDevices = selectedDevices.filter(d => d.device_type_id !== devData.device_type_id);
            }
            toDatepickerBtn.disabled = selectedDevices.length === 0;
          });
        });

        //disable the next button until devices are selected
        toDatepickerBtn.disabled = true;
      }

      //when the user clicks next to proceed to date selection, render date pickers and advance carousel
      toDatepickerBtn.addEventListener('click', () => {
        renderDatePickers();
        carousel.next();
      });

      //dynamically render date pickers for each device group (home department) with constraints
      function renderDatePickers() {
        datePickersContainer.innerHTML = '';

        //group selected devices by home_department and determine minimum max loan days per group
        const grouped = selectedDevices.reduce((acc, device) => {
          const hd = device.home_department == 0 ? 'all' : device.home_department;
          if (!acc[hd]) {
            acc[hd] = {devices: [], minMaxLoanDays: device.max_loan_days};
          }
          acc[hd].devices.push(device);
          if (device.max_loan_days < acc[hd].minMaxLoanDays) {
            acc[hd].minMaxLoanDays = device.max_loan_days;
          }
          return acc;
        }, {});

        //create date picker input sets for each home department group
        for (const homeDept in grouped) {
          const group = grouped[homeDept];

          const groupDiv = document.createElement('div');
          groupDiv.className = 'mb-4 border p-3';

          const deptName = homeDept === 'all' ? 'Für alle Institute' : `Department ${homeDept}`;
          const h5 = document.createElement('h5');
          h5.textContent = `${deptName} - Geräte: ${group.devices.map(d => d.device_type_name).join(', ')}`;
          groupDiv.appendChild(h5);

          //create pickup date input
          const startInput = document.createElement('input');
          startInput.type = 'text';
          startInput.className = 'form-control mb-2';
          startInput.placeholder = 'Abholdatum wählen';
          startInput.id = `startDate-${homeDept}`;
          startInput.required = true;

          //create return date input
          const endInput = document.createElement('input');
          endInput.type = 'text';
          endInput.className = 'form-control';
          endInput.placeholder = 'Rückgabedatum wählen';
          endInput.id = `endDate-${homeDept}`;
          endInput.required = true;

          groupDiv.appendChild(startInput);
          groupDiv.appendChild(endInput);

          //add the group div to the date pickers container
          datePickersContainer.appendChild(groupDiv);

          //determine allowed weekdays for the department
          const allowedDays = (rentDays[homeDept] || []).map(rd => parseInt(rd.dayofweek)); // e.g. [1,3,5]
          const maxLoanDays = group.minMaxLoanDays;
          const isSuperadmin = <?php echo json_encode(is_superadmin($user_username)); ?>;

          //initialize Flatpickr for pickup date with restrictions
          flatpickr(startInput, {
            minDate: isSuperadmin ? "today" : new Date().fp_incr(7),
            disable: [
              (date) => !allowedDays.includes(date.getDay() === 0 ? 7 : date.getDay())
            ],
            onChange: function(selectedDates) {
              if (selectedDates.length > 0) {
                endPicker.set('minDate', selectedDates[0]);
                endPicker.set('maxDate', selectedDates[0].fp_incr(maxLoanDays));
              }
            }
          });

          //initialize Flatpickr for return date with same weekday restrictions
          const endPicker = flatpickr(endInput, {
            minDate: "today",
            disable: [
              (date) => !allowedDays.includes(date.getDay() == 0 ? 7 : date.getDay())
            ]
          });
        }
      }

      //on window load, reset carousel to first step
      window.addEventListener('load', function() {
        const carouselElement = document.querySelector('#orderCarousel');
        if (carouselElement) {
          let carousel = bootstrap.Carousel.getInstance(carouselElement);
          if (!carousel) {
            carousel = new bootstrap.Carousel(carouselElement, { interval: false });
          }
          setTimeout(() => carousel.to(0), 100); // Verzögerung 100ms
        }

        //reset all selects and buttons to initial state
        const departmentSelect = document.getElementById('departmentSelect');
        if (departmentSelect) {
          departmentSelect.value = '';
        }
        
        const toDevicesBtn = document.getElementById('toDevices');
        if (toDevicesBtn) {
          toDevicesBtn.disabled = true;
        }
        
        const toDatepickerBtn = document.getElementById('toDatepicker');
        if (toDatepickerBtn) {
          toDatepickerBtn.disabled = true;
        }
      });

      //handle form submission via AJAX to avoid page reload
      document.getElementById('bestellForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const form = this;
        const formData = new FormData(form);
        formData.append('reservation', '1');

        fetch(form.action, {
          method: form.method,
          body: formData,
        })
        .then(response => response.text())
        .then(data => {

          //reset carousel to first step after successful submission
          const carouselElement = document.querySelector('#orderCarousel');
          if (carouselElement) {
            let carousel = bootstrap.Carousel.getInstance(carouselElement);
            if (!carousel) {
              carousel = new bootstrap.Carousel(carouselElement, { interval: false });
            }
            carousel.to(0);
          }

          //reset form fields and disable buttons
          form.reset();
          document.getElementById('toDevices').disabled = true;
          document.getElementById('toDatepicker').disabled = true;

          //give alert to the user
          alert("Reservierung erfolgreich gespeichert!");
        })
        .catch(error => {
          console.error('Fehler beim Absenden des Formulars:', error);
          alert("Fehler bei der Reservierung. Bitte versuche es erneut.");
        });
      });
    </script>
    <?php if (is_superadmin($user_username)): ?>
      <div class="d-flex justify-content-center">
        <a href="admini.php" class="btn btn-outline-dark d-flex align-items-center justify-content-center" style="width:350px; margin: 20px;">
          <i class="fa fa-user-gear me-2"></i> Admin Seite
        </a>
      </div>
    <?php endif; ?>
  </body>
</html>
