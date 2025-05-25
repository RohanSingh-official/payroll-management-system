<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Payroll System</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Human Resources
    </div>

    <!-- Nav Item - Employees -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php' || basename($_SERVER['PHP_SELF']) == 'employee.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/employees.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Employees</span>
        </a>
    </li>

    <!-- Nav Item - Departments -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'departments.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/departments.php">
            <i class="fas fa-fw fa-building"></i>
            <span>Departments</span>
        </a>
    </li>

    <!-- Nav Item - Attendance -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/attendance.php">
            <i class="fas fa-fw fa-calendar-check"></i>
            <span>Attendance</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Payroll Management
    </div>

    <!-- Nav Item - Payroll -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'payroll.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/payroll.php">
            <i class="fas fa-fw fa-money-check-alt"></i>
            <span>Payroll</span>
        </a>
    </li>

    <!-- Nav Item - Allowances & Deductions -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'allowances.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/allowances.php">
            <i class="fas fa-fw fa-plus-minus"></i>
            <span>Allowances & Deductions</span>
        </a>
    </li>

    <!-- Nav Item - Payslips -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'payslips.php' || basename($_SERVER['PHP_SELF']) == 'payslip.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/payslips.php">
            <i class="fas fa-fw fa-file-invoice-dollar"></i>
            <span>Payslips</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Reports & Analytics
    </div>

    <!-- Nav Item - Reports -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/reports.php">
            <i class="fas fa-fw fa-chart-bar"></i>
            <span>Reports</span>
        </a>
    </li>

    <!-- Nav Item - Payroll Report -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'payroll_report.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/payroll_report.php">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Payroll Report</span>
        </a>
    </li>

    <!-- Nav Item - Leave Management -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'leave.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/leave.php">
            <i class="fas fa-fw fa-calendar-minus"></i>
            <span>Leave Management</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        System
    </div>

    <!-- Nav Item - Settings -->
    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php' || basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="../pages/settings.php">
            <i class="fas fa-fw fa-cog"></i>
            <span>Settings</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->