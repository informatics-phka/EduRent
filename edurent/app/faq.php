<!DOCTYPE html>
<<<<<<< Updated upstream
=======
<?php
if($debug){
	ini_set('display_errors', '1');     
	ini_set('display_startup_errors', '1');     
	error_reporting(E_ALL);
}

check_is_admin($user_username);

$is_superadmin = is_superadmin($user_username);



?>

>>>>>>> Stashed changes
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<!-- JQuery -->
	<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
	<script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<!-- Bootstrap -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<!-- stylesheet -->
	<link rel="stylesheet" href="style-css/rent.css">
	<link rel="stylesheet" href="style-css/toasty.css">
    <link rel="stylesheet" href="style-css/accessability.css">
	<style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f8f9fa;
        }
        .faq-section {
            margin-bottom: 20px;
        }
        .faq-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .faq-question {
            background: #ffffff;
            border: 1px solid #ddd;
            padding: 10px;
            cursor: pointer;
            margin: 5px 0;
        }
        .faq-answer {
            display: none;
            padding: 10px;
            border-left: 2px solid #007BFF;
            background: #e9ecef;
        }
        #search {
            background: #e9ecef;
            border: 1px solid #ced4da;
            color: #495057;
        }
    </style>
</head>

<body>
<<<<<<< Updated upstream
	<div class="container">	
=======
	<div class="main">
		<?php require_once 'navbar.php'; ?>
		<br>
					
>>>>>>> Stashed changes
		<!-- Searchbar -->
		<div class="mb-4">
            <div class="input-group">
                <input type="text" id="search" class="form-control" placeholder="Suchen...">
            </div>
        </div>

		<!-- Initial Configuration -->
		<div class="faq-section" data-title="Initial Configuration">
			<div class="faq-title">Initial Configuration</div>
			<div class="faq-item">
				<div class="faq-question">How to configure EduRent after the installation?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/departments">departments</a></li>
						<li class="mb-2">Add a new department, then save the changes</li>
						<li class="mb-2">Open the newly created department</li>
						<li class="mb-2">Scroll to the bottom</li>
						<li class="mb-2">Add a device type for the department by clicking on Add</li>
						<li class="mb-2">open the newly created device type</li>
						<li class="mb-2">Add devices for the device type by clicking on Add, then saving after each entry</li>
						<li class="mb-2">click on back</li>
						<li class="mb-2">Under Pickup Days, define the available days for the department, following the guided process</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- System Administrator -->
		<div class="faq-section" data-title="System Administrator">
			<div class="faq-title">System Administrator</div>
			<div class="faq-item">
				<div class="faq-question">How to change the MySQL Database?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to the db_connect.php file</li>
						<li class="mb-2">Set the new informations for the $host, $username, $password und the $databaseName.</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">Where are the logs stored?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">The logs are stored in the edurent/log folder</li>
						<li class="mb-2">Pay attetion, that the superadmin can change or delete the log files. There are no backups!</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Super Administrator -->
		<div class="faq-section" data-title="Super Administrator">
			<div class="faq-title">Super Administrator</div>
			<div class="faq-item">
				<div class="faq-question">How to change the lead time?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">empty</li>
					</u>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to change the maximum rent days?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">empty</li>
					</u>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to change the service status?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">empty</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to set the admin rights of a user?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/admins">admins</a></li>
						<li class="mb-2">Click on the "Add" button at the top</li>
						<li class="mb-2">Select the user you would like to grant admin right at the top</li>
						<li class="mb-2">Select the admin right the user should have. You can specific departments or grant super admin right with the button "All".</li>
						<li class="mb-2">Click on the "Confirm" button at the buttom to save the settings.</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to change the admin rights of an admin?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/admins">admins</a></li>
						<li class="mb-2">Select the admin you would like to edit.</li>
						<li class="mb-2">Select the admin right the admin should now have. You can specific departments or grant super admin right with the button "All".</li>
						<li class="mb-2">Click on the "Save" button at the buttom to save the settings.</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to remove an admin?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/admins">admins</a></li>
						<li class="mb-2">Select the admin you would like to edit.</li>
						<li class="mb-2">Scroll all the way down.</li>
						<li class="mb-2">Click on the "Delete" button at the buttom to remove the admin.</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to add a department?</div>
				<div class="faq-answer">
					<ul>
					<li class="mb-2">empty</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Deaprtment -->
		<div class="faq-section" data-title="Devices">
			<div class="faq-title">Devices</div>
			<div class="faq-item">
				<div class="faq-question">How to delete a department</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/departments">departments</a></li>
						<li class="mb-2">Open the department you would like to delete</li>
						<li class="mb-2">Delete every device type of the department</li>
						<li class="mb-2">After that delete the department</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to edit a department</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">empty</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to change the pick up days?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/departments">departments</a></li>
						<li class="mb-2">Open the department you would like to edit</li>
						<li class="mb-2">Click on "Pick up days" after the department settings</li>
						<li class="mb-2">Here you can add, edit or delete pick up days. The timespan is a textfeld, you can also write something like "By arrangement only".</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to change the pick up room?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/departments">departments</a></li>
						<li class="mb-2">Open the department you would like to edit</li>
						<li class="mb-2">edit the department setting "Room of the department"</li>
						<li class="mb-2">To save the new room click on "Save"</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Device types -->
		<div class="faq-section" data-title="Device types">
			<div class="faq-title">Device types</div>
			<div class="faq-item">
				<div class="faq-question">How to add a new device type?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">Navigate to <a href="https://innovationspace.ph-karlsruhe.de/edurent/departments">departments</a></li>
						<li class="mb-2">Open the department you would like to edit</li>
						<li class="mb-2">edit the department setting "Room of the department"</li>
						<li class="mb-2">To save the new room click on "Save"</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to edit a device type?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">connect to the <a href="https://innovationspace.ph-karlsruhe.de/edurent/admini">admin page</a></li>
						<li class="mb-2">open the settings by clicking "Device management" at the very bottom</li>
						<li class="mb-2">click on the device type name you would like to edit</li>
						<li class="mb-2">edit the information you want to modify</li>
						<li class="mb-2">finish the device type edit by clicking on "Save""</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Devices -->
		<div class="faq-section" data-title="Devices">
			<div class="faq-title">Devices</div>
			<div class="faq-item">
				<div class="faq-question">How to add a new device?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">connect to the <a href="https://innovationspace.ph-karlsruhe.de/edurent/admini">admin page</a></li>
						<li class="mb-2">open the settings by clicking "Device management" at the very bottom</li>
						<li class="mb-2">click on the device type name you would like to add a device to</li>
						<li class="mb-2">click on "Add"</li>
						<li class="mb-2">enter the device infos</li>
						<li class="mb-2">finish the device creation by clicking on "Confirm"</li>
					</ul>
				</div>
			</div>
			<div class="faq-item">
				<div class="faq-question">How to edit a device?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">connect to the <a href="https://innovationspace.ph-karlsruhe.de/edurent/admini">admin page</a></li>
						<li class="mb-2">open the settings by clicking "Device management" at the very bottom</li>
						<li class="mb-2">click on the device type to which the device belongs</li>
						<li class="mb-2">click on the device tag you would like to edit</li>
						<li class="mb-2">change the device infos</li>
						<li class="mb-2">finish the device type edit by clicking on "Save""</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Reservations -->
		<div class="faq-section" data-title="Reservation">
			<div class="faq-title">Reservation</div>
			<div class="faq-item">
				<div class="faq-question">What is the reservation procedure and how is it performed?</div>
				<div class="faq-answer">
					<ul>
						<li class="mb-2">connect to the <a href="https://innovationspace.ph-karlsruhe.de/edurent/admini">admin page</a></li>
						<li class="mb-2">click on the reservation you would like to edit</li>
						<li class="mb-2">With the green button you can continue the reservation process. With the orange button you can close the window and the red button is for downgrading.</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Button -->
		<div class='row justify-content-center'>
			<div class='col-md-6 mb-3'>
				<a class='btn btn-secondary btn-block' href='admini'>
					<i class="fas fa-arrow-left mr-2"></i>
					<?php echo translate('word_back'); ?>
				</a>
			</div>
		</div>
	</div>
	
	<script>
        document.addEventListener("DOMContentLoaded", function () {
			// Suchleiste
			const searchInput = document.getElementById("faqSearch");

			searchInput.addEventListener("input", function () {
				const searchTerm = searchInput.value.toLowerCase();
				const accordions = document.querySelectorAll(".accordion");

				accordions.forEach(function (accordion) {
					const headerText = accordion.querySelector(".accordion-header button").textContent.toLowerCase();
					const bodyText = accordion.querySelector(".card-body") ? accordion.querySelector(".card-body").textContent.toLowerCase() : "";
					if (headerText.includes(searchTerm) || bodyText.includes(searchTerm)) {
						accordion.style.display = "block";
						const header = document.querySelectorAll(".faq_title");
					} else {
						accordion.style.display = "none";

					}
				});
				
			});
		});

		// Alle Fragen-Elemente auswählen
        const questions = document.querySelectorAll('.faq-question');

        questions.forEach(question => {
            question.addEventListener('click', () => {
                // Die nächste Antwort umschalten
                const answer = question.nextElementSibling;
                const isVisible = answer.style.display === 'block';

                // Alle Antworten schließen
                document.querySelectorAll('.faq-answer').forEach(ans => {
                    ans.style.display = 'none';
                });

                // Nur die aktuelle Antwort einblenden, wenn sie nicht sichtbar war
                if (!isVisible) {
                    answer.style.display = 'block';
                }
            });
        });

        // Suchleiste implementieren
        const searchInput = document.getElementById('search');
        searchInput.addEventListener('input', () => {
            const searchString = searchInput.value.toLowerCase();
            const sections = document.querySelectorAll('.faq-section');

            sections.forEach(section => {
                const title = section.getAttribute('data-title').toLowerCase();
                const items = section.querySelectorAll('.faq-item');
                let sectionHasMatch = false;

                items.forEach(item => {
                    const question = item.querySelector('.faq-question').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer').textContent.toLowerCase();

                    if (question.includes(searchString) || answer.includes(searchString)) {
                        item.style.display = 'block';
                        sectionHasMatch = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (title.includes(searchString) || sectionHasMatch) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
<?php
echo $OUTPUT->footer();
?>