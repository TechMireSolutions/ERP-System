# ERP System

A comprehensive Enterprise Resource Planning (ERP) system built using the CodeIgniter PHP framework. This application is designed to streamline business processes including HR, Payroll, Project Management, and Recruitment.

## Features

### Human Resource Management

- **Employee Management**: Manage employee profiles, roles, and designations.
- **Attendance & Timesheets**: Track employee attendance and work hours.
- **Leave Management**: Handle leave requests and approvals.
- **Payroll**: Manage salaries, payslips, and expenses.
- **Awards & Complaints**: Record employee achievements and grievances.
- **Travel & Transfers**: Manage employee travel requests and department transfers.
- **Resignation & Termination**: Handle exit processes.

### Recruitment & Talent Acquisition

- **Job Posting**: Create and manage job openings.
- **Candidate Management**: Track job applicants.
- **Interviews**: Schedule and manage job interviews.

### Project Management

- **Projects**: Create and track projects.
- **Tasks & Tickets**: Assign tasks and manage support tickets.
- **Timesheets**: Monitor time spent on projects.

### Performance & Training

- **Performance Appraisal**: Evaluate employee performance.
- **Training**: Manage training programs, trainers, and employee participation.

### Accounting

- **Accounting**: Basic accounting and expense tracking.

### System Settings

- **Roles & Permissions**: Granular access control.
- **Departments & Designations**: Configure organizational structure.
- **Announcements**: Broadcast messages to employees.

## Requirements

- **PHP**: Version 5.2.4 or newer (Recommended: 7.x or 8.x depending on CI version compatibility).
- **Database**: MySQL or MariaDB.
- **Web Server**: Apache or Nginx.

## Installation

1.  **Clone the Repository**

    ```bash
    git clone <repository-url>
    ```

2.  **Database Setup**
    - Create a new MySQL database.
    - Import the provided SQL dump file (usually located in the root or `database` folder) into your database.

3.  **Configuration**
    - Open `application/config/database.php` and update the database connection settings:
      ```php
      $db['default'] = array(
          'dsn'   => '',
          'hostname' => 'localhost',
          'username' => 'your_db_username',
          'password' => 'your_db_password',
          'database' => 'your_db_name',
          // ...
      );
      ```
    - Open `application/config/config.php` and set the base URL:
      ```php
      $config['base_url'] = 'http://localhost/your_project_folder/';
      ```

4.  **Run the Application**
    - Host the application on your web server (e.g., XAMPP, WAMP, or LAMP stack).
    - Access the application via your web browser.

## License

This project uses the CodeIgniter framework, which is licensed under the MIT License.
