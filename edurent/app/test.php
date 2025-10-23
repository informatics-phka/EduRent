<?php

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
}

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

load_departments();
load_device_types();

?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
<title></title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
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
              <?php foreach ($departments as $dep):
                // Überspringe Department mit IDs 0, -1 und leere IDs
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
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-secondary" data-bs-target="#orderCarousel" data-bs-slide="prev">
                <i class="bi bi-chevron-compact-left"></i> Zurück
              </button>
              <button type="submit" class="btn btn-success">
                Bestellung bestätigen
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>
  </form>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
const deviceTypes = <?php echo json_encode($device_types); ?>;
const rentDays = {};
<?php
foreach ($departments as $dep) {
  $rid = intval($dep['department_id']);
  $rdays = getRentDaysByDepartmentId($rid);
  echo "rentDays[$rid] = " . json_encode($rdays) . ";\n";
}
?>

const carouselElement = document.querySelector('#orderCarousel');
const carousel = new bootstrap.Carousel(carouselElement, { interval: false });

const departmentSelect = document.getElementById('departmentSelect');
const toDevicesBtn = document.getElementById('toDevices');
const deviceListContainer = document.getElementById('deviceListContainer');
const toDatepickerBtn = document.getElementById('toDatepicker');
const datePickersContainer = document.getElementById('datePickersContainer');

let selectedDepartment = null;
let selectedDevices = [];

const departmentsMap = {};
<?php foreach($departments as $dep): ?>
  departmentsMap[<?php echo json_encode($dep['department_id']); ?>] = <?php echo json_encode($dep['department_de']); ?>;
<?php endforeach; ?>
departmentsMap[0] = "Für alle Institute";

departmentSelect.addEventListener('change', () => {
  selectedDepartment = departmentSelect.value;
  toDevicesBtn.disabled = !selectedDepartment;
});

toDevicesBtn.addEventListener('click', () => {
  loadDevices(selectedDepartment);
  carousel.next();
});

function loadDevices(departmentId) {
  deviceListContainer.innerHTML = '';
  selectedDevices = [];

  const deptIdInt = parseInt(departmentId);

  const filteredDevices = deviceTypes.filter(dev => {
    return dev.available_for === 0 || dev.available_for === deptIdInt;
  });

  if (filteredDevices.length === 0) {
    deviceListContainer.innerHTML = '<p>Keine Geräte verfügbar.</p>';
    toDatepickerBtn.disabled = true;
    return;
  }


  const grouped = filteredDevices.reduce((acc, dev) => {
    if (!acc[dev.home_department]) acc[dev.home_department] = [];
    acc[dev.home_department].push(dev);
    return acc;
  }, {});

let html = '';
for (const homeDept in grouped) {
  const group = grouped[homeDept];

  const groupName = (departmentsMap.hasOwnProperty(homeDept)) ? departmentsMap[homeDept] : `Institut ${homeDept}`;
  html += `<table class="table"><thead><tr><th colspan="5" class="bg-info text-white">${groupName}</th></tr></thead><tbody>`;
  
  group.forEach(dev => {
    html += '<tr>';
    html += `<td><input type="checkbox" class="device-checkbox" data-device='${JSON.stringify(dev)}'></td>`;
    html += `<td><img src="${dev.device_type_img_path}" alt="${dev.device_type_name}" style="width:80px;"></td>`;
    html += `<td><strong>${dev.device_type_name}</strong></td>`;
    html += `<td>${dev.device_type_info}</td>`; //<br><small>${dev.tooltip || ''}</small>
    html += `<td>Für 1-${dev.max_loan_days} Tage ausleihen</td>`;
    html += '</tr>';
  });
  
  html += '</tbody></table>';
}

  deviceListContainer.innerHTML = html;

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

  toDatepickerBtn.disabled = true;
}

toDatepickerBtn.addEventListener('click', () => {
  renderDatePickers();
  carousel.next();
});

function renderDatePickers() {
  datePickersContainer.innerHTML = '';

  //Sort devices for pickup and return days
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

  for (const homeDept in grouped) {
    const group = grouped[homeDept];

    const groupDiv = document.createElement('div');
    groupDiv.className = 'mb-4 border p-3';

    const deptName = homeDept === 'all' ? 'Für alle Institute' : `Department ${homeDept}`;
    const h5 = document.createElement('h5');
    h5.textContent = `${deptName} - Geräte: ${group.devices.map(d => d.device_type_name).join(', ')}`;
    groupDiv.appendChild(h5);

    // pickup days
    const startInput = document.createElement('input');
    startInput.type = 'text';
    startInput.className = 'form-control mb-2';
    startInput.placeholder = 'Abholdatum wählen';
    startInput.id = `startDate-${homeDept}`;
    startInput.required = true;

    // return days
    const endInput = document.createElement('input');
    endInput.type = 'text';
    endInput.className = 'form-control';
    endInput.placeholder = 'Rückgabedatum wählen';
    endInput.id = `endDate-${homeDept}`;
    endInput.required = true;

    groupDiv.appendChild(startInput);
    groupDiv.appendChild(endInput);

    datePickersContainer.appendChild(groupDiv);
    
    const allowedDays = (rentDays[homeDept] || []).map(rd => parseInt(rd.dayofweek)); // z.B. [1,3,5]
    const maxLoanDays = group.minMaxLoanDays;

    flatpickr(startInput, {
      minDate: "today",
      disable: [
        (date) => !allowedDays.includes(date.getDay() == 0 ? 7 : date.getDay())
      ],
      onChange: function(selectedDates) {
        if (selectedDates.length > 0) {
          endPicker.set('minDate', selectedDates[0]);
          endPicker.set('maxDate', selectedDates[0].fp_incr(maxLoanDays));
        }
      }
    });

    const endPicker = flatpickr(endInput, {
      minDate: "today",
      disable: [
        (date) => !allowedDays.includes(date.getDay() == 0 ? 7 : date.getDay())
      ]
    });
  }
}

</script>

</body>
</html>