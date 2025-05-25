# Payroll Management System

A web-based Payroll Management System designed for managing employee details, payroll, attendance, leaves, departments, and payslips efficiently. Built using PHP and MySQL.

## 📁 Project Structure

- `index.php` - Main entry point (login/dashboard).
- `add_user.php` - Admin panel to add new users.
- `database/payroll_db.sql` - SQL file to set up the database.
- `includes/` - Contains reusable components:
  - `config.php` - Database configuration.
  - `header.php`, `footer.php`, `sidebar.php` - UI structure.
  - `logout.php` - Logout functionality.
- `pages/` - Feature pages:
  - `dashboard.php` - Admin overview dashboard.
  - `employees.php`, `add_employee.php` - Manage employees.
  - `departments.php` - Handle departments.
  - `payroll.php`, `payslip.php`, `payslips.php` - Payroll processing and reports.
  - `attendance.php`, `attendance_report.php` - Attendance features.
  - `leave.php` - Leave management.

## 🛠 Setup Instructions

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/payroll-management-system.git
   cd payroll-management-system
