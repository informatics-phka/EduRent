<!-- ABOUT THE PROJECT -->
## About The Project

EduRent is a reservation platform designed to manage the device and equipment loans for institutions using Moodle. It allows universities or educational institutions to easily handle the rental of devices such as laptops, projectors, or other tech equipment, streamlining the process for staff and students alike.

### Built With

* [Bootstrap 5.1](https://getbootstrap.com/docs/5.1/getting-started/introduction)
* [Toasty](http://jakim.me/Toasty.js/)
* [Daterangepicker](http://www.daterangepicker.com)
* [Moodle](https://moodle.org)
* [PhpMailer](https://github.com/PHPMailer/PHPMailer)


<!-- GETTING STARTED -->
## Getting Started
To install EduRent on Moodle, follow these steps:

### Prerequisites
- A Moodle installation
- A working MySQL database
- Admin access to the server via SFTP

### Installation
1. Download the plugin as a `.zip` file
2. Extract the contents of the `.zip` file and open the folder in your code editor.
3. Edit the `Controller\mailer.php` file:
- Configure the SMTP settings:
  - **SMTP Username**
  - **SMTP Password**
  - **SMTP Host**

4. Edit the `Controller\db_connect.php` file:
- Set the following database connection parameters:
  - **MySQL Host**
  - **Username**
  - **Password**
  - **Database Name**
5. Save the changes in both `mailer.php` and `db_connect.php`.
6. Use SFTP to upload the `edurent` folder to the Moodle directory.
8. Once the insatlaltion is complete, navigate to the EduRent plugin page at `[your_moodle_page]/edurent` to start the process

### Configuration
1. Import the database from `edurent.sql` to initialize the required tables.
2. Upon visiting the platform for the first time, a user will be created.
3. Set a Super Admin in the database under the `admins` table, and set the department as `0`.
4. Reload the page for the Super Admin. If everything works, the user will see the **Admin Page** button.
5. Open the **Admin Page**.
6. Navigate to **Departments** and add a new department, then save the changes.
7. Add devices for the department by clicking on **Add**, then saving after each entry.
8. Under **Pickup Days**, define the available days for the department, following the guided process.
9. You can assign department admins by navigating to **Admin Page** and clicking on **Admins**. Super Admins can create and assign multiple admins to different departments.

<!-- USAGE -->

## Usage

Once installed, the EduRent platform can be accessed via [your_moodle_page]/edurent.

The system provides a user-friendly interface that allows both administrators and general users to manage and reserve devices. Administrators have the ability to add new equipment, create and edit reservation slots, and manage institution settings.

## Troubleshooting

If you encounter any issues during installation or configuration, here are some common troubleshooting steps:

- Database Connection Issues:
  - Double-check the settings in db_connect.php (host, username, password, database name).
  - Ensure that your MySQL server is running and the credentials provided are correct.
- SMTP Configuration Issues:
  - Verify the SMTP settings in mailer.php (host, username, password, port).
  - Check if your email provider requires special security settings or restrictions.
- Permissions:
  - Ensure that the edurent directory and files have the appropriate permissions for the web server to read and execute.


<!-- CONTRIBUTING -->

## Contributing

Currently, contributions to EduRent are managed privately, and only invited individuals have access to the repository. However, we are actively working on the platform and plan to make it publicly available in the future.

<!-- LICENSE -->
## License

The licensing for this project is under review. Information will be added once the legal aspects are finalized.


<!-- CONTACT -->
## Contact

If you have any questions or need further information, feel free to contact:

- **Prof. Dr. techn. Bernhard Standl**  
  [Institute of Informatics and Digital Education](https://en.ph-karlsruhe.de/research/institute-of-informatics-and-digital-education) | [PHKA Profile](https://www.ph-karlsruhe.de/personen/detail/Bernhard_Standl_137)  
  📧 **Email:** [bernhard.standl@ph-karlsruhe.de](mailto:bernhard.standl@ph-karlsruhe.de)
- **Dr. Nico Hillah**  
  [Institute of Informatics and Digital Education](https://en.ph-karlsruhe.de/research/institute-of-informatics-and-digital-education) | [PHKA Profile](https://www.ph-karlsruhe.de/personen/detail/Nico_Hillah_6117)  
  📧 **Email:** [nico.hillah@ph-karlsruhe.de](mailto:nico.hillah@ph-karlsruhe.de)
- **Norbert Varney**  
  [Institute of Informatics and Digital Education](https://en.ph-karlsruhe.de/research/institute-of-informatics-and-digital-education) | 
  📧 **Email:** [norbert.varney@stud.ph-karlsruhe.de](mailto:norbert.varney@stud.ph-karlsruhe.de)
