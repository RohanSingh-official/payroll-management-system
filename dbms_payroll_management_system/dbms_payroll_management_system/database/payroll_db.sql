-- Create Database
CREATE DATABASE IF NOT EXISTS payroll_management;
USE payroll_management;

-- Users Table (for admin login)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'hr') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
    dept_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL,
    dept_head VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
    emp_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    dept_id INT,
    position VARCHAR(100),
    hire_date DATE,
    basic_salary DECIMAL(10, 2) NOT NULL,
    bank_account VARCHAR(50),
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE SET NULL
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM('present', 'absent', 'half_day', 'leave') DEFAULT 'present',
    notes TEXT,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
    UNIQUE KEY (emp_id, date)
);

-- Leave Types Table
CREATE TABLE IF NOT EXISTS leave_types (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type VARCHAR(50) NOT NULL,
    description TEXT,
    allowed_days INT NOT NULL
);

-- Leave Applications Table
CREATE TABLE IF NOT EXISTS leave_applications (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    applied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id)
);

-- Payroll Table
CREATE TABLE IF NOT EXISTS payroll (
    payroll_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    overtime_hours DECIMAL(5, 2) DEFAULT 0,
    overtime_rate DECIMAL(10, 2) DEFAULT 0,
    allowances DECIMAL(10, 2) DEFAULT 0,
    deductions DECIMAL(10, 2) DEFAULT 0,
    tax DECIMAL(10, 2) DEFAULT 0,
    net_salary DECIMAL(10, 2) NOT NULL,
    payment_date DATE,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE
);

-- Allowance Types Table
CREATE TABLE IF NOT EXISTS allowance_types (
    allowance_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Employee Allowances Table
CREATE TABLE IF NOT EXISTS employee_allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    allowance_type_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    effective_date DATE NOT NULL,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
    FOREIGN KEY (allowance_type_id) REFERENCES allowance_types(allowance_type_id)
);

-- Deduction Types Table
CREATE TABLE IF NOT EXISTS deduction_types (
    deduction_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Employee Deductions Table
CREATE TABLE IF NOT EXISTS employee_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    deduction_type_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    effective_date DATE NOT NULL,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
    FOREIGN KEY (deduction_type_id) REFERENCES deduction_types(deduction_type_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '$2y$10$8zUkFX1tXY5Gy/DxiYwOyeYXJgQUDkNpCOv.62R.aNY9HUZ1CbVxe', 'admin@payroll.com', 'System Administrator', 'admin');

-- Insert sample departments
INSERT INTO departments (dept_name, dept_head, description) VALUES
('Human Resources', 'John Smith', 'Manages recruitment, employee relations, and HR policies'),
('Finance', 'Jane Doe', 'Handles financial operations and accounting'),
('Information Technology', 'Mike Johnson', 'Manages IT infrastructure and software development'),
('Marketing', 'Sarah Williams', 'Handles marketing campaigns and brand management'),
('Operations', 'Robert Brown', 'Manages day-to-day operations');

-- Insert sample leave types
INSERT INTO leave_types (leave_type, description, allowed_days) VALUES
('Annual Leave', 'Regular vacation leave', 20),
('Sick Leave', 'Leave due to illness', 10),
('Maternity Leave', 'Leave for new mothers', 90),
('Paternity Leave', 'Leave for new fathers', 7),
('Bereavement Leave', 'Leave due to death of family member', 3);

-- Insert sample allowance types
INSERT INTO allowance_types (name, description) VALUES
('Housing', 'Housing allowance'),
('Transportation', 'Transportation allowance'),
('Meal', 'Meal allowance'),
('Medical', 'Medical allowance');

-- Insert sample deduction types
INSERT INTO deduction_types (name, description) VALUES
('Income Tax', 'Government income tax'),
('Provident Fund', 'Employee provident fund contribution'),
('Health Insurance', 'Health insurance premium'),
('Loan Repayment', 'Employee loan repayment');